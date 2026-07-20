<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.4
 */

namespace cinghie\userextended\controllers;

use Yii;
use cinghie\userextended\helpers\LoginRateLimiter;
use cinghie\userextended\helpers\SecurityAudit;
use cinghie\userextended\helpers\SessionHelper;
use cinghie\userextended\models\LoginForm;
use dektrium\user\controllers\SecurityController as BaseController;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * Class SecurityController
 */
class SecurityController extends BaseController
{
	/**
	 * @return bool|string
	 */
	public function getViewPath()
	{
		return Yii::getAlias('@vendor/cinghie/yii2-user-extended/views/adminlte/security');
	}

	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		$behaviors = parent::behaviors();

		if (isset($behaviors['access']['rules']) && is_array($behaviors['access']['rules'])) {
			$behaviors['access']['rules'][] = [
				'allow' => true,
				'actions' => ['session-ping'],
				'roles' => ['@'],
			];
		} else {
			$behaviors['access'] = [
				'class' => AccessControl::class,
				'rules' => [
					['allow' => true, 'actions' => ['session-ping'], 'roles' => ['@']],
				],
			];
		}

		if (!isset($behaviors['verbs'])) {
			$behaviors['verbs'] = [
				'class' => VerbFilter::class,
				'actions' => [],
			];
		}
		$behaviors['verbs']['actions']['session-ping'] = ['get', 'head'];

		return $behaviors;
	}

	/**
	 * Lightweight keep-alive for client session heartbeat (no HTML body).
	 *
	 * @return Response
	 */
	public function actionSessionPing()
	{
		Yii::$app->response->format = Response::FORMAT_RAW;
		Yii::$app->response->statusCode = 204;
		Yii::$app->response->content = '';

		return Yii::$app->response;
	}

	/**
	 * Displays the login page.
	 *
	 * @return string|Response
	 * @throws ExitException
	 * @throws InvalidConfigException
	 */
	public function actionLogin()
	{
		if (!Yii::$app->user->isGuest) {
			$this->goHome();
		}

		/** @var LoginForm $model */
		$model = Yii::createObject(LoginForm::class);
		$event = $this->getFormEvent($model);
		$limiter = LoginRateLimiter::create();

		$this->performAjaxValidation($model);
		$this->trigger(self::EVENT_BEFORE_LOGIN, $event);

		if (Yii::$app->request->get('expired')) {
			Yii::$app->session->setFlash('login', Yii::t('userextended', 'Your session has expired. Please sign in again.'));
			// Log once per session to avoid refresh/bookmark spam
			if (!Yii::$app->session->get('userextended.expire_client_logged')) {
				SecurityAudit::log('session_expire_client', 0, [
					'reason' => 'expired_query',
				], 'session', 'User', '/user/security/login');
				Yii::$app->session->set('userextended.expire_client_logged', 1);
			}
		} else {
			Yii::$app->session->setFlash('login', Yii::t('userextended', 'Type your credentials'));
		}

		if ($model->load(Yii::$app->getRequest()->post())) {
			$wasLocked = $limiter->isLocked($model->login);

			if ($model->login()) {
				$limiter->clear($model->login);
				$this->regenerateSessionIdIfEnabled();
				Yii::$app->session->remove('userextended.expire_client_logged');
				Yii::$app->session->setFlash('login', Yii::t('userextended', 'Login successful'));
				SecurityAudit::log(
					'login_success',
					Yii::$app->user->id ? (int) Yii::$app->user->id : 0,
					[
						'login' => SecurityAudit::safeLogin($model->login),
					],
					'auth',
					'User',
					'/user/security/login'
				);
				$this->trigger(self::EVENT_AFTER_LOGIN, $event);
				return $this->goBack();
			}

			if ($limiter->isEnabled() && !$wasLocked && $model->shouldCountAsLoginFailure()) {
				$limiter->recordFailure($model->login);
				$limiter->applyDelay($model->login);
			}

			$reason = $this->resolveLoginFailureReason($model, $limiter);
			SecurityAudit::log('login_fail', 0, [
				'login' => SecurityAudit::safeLogin($model->login),
				'reason' => $reason,
				'locked' => $limiter->isLocked($model->login),
			], 'auth', 'User', '/user/security/login');

			Yii::$app->session->setFlash('login', $this->resolveLoginFailureFlash($model, $limiter));
		}

		$view = Yii::$app->getModule('userextended')->templateLogin;

		return $this->render($view, [
			'model' => $model,
			'module' => $this->module,
		]);
	}

	/**
	 * @param LoginForm $model
	 * @param LoginRateLimiter $limiter
	 *
	 * @return string
	 */
	protected function resolveLoginFailureFlash(LoginForm $model, LoginRateLimiter $limiter): string
	{
		if ($limiter->isLocked($model->login)) {
			return Yii::t('userextended', 'Too many failed login attempts. Please try again later.');
		}

		if ($model->hasErrors('turnstileToken')) {
			return Yii::t('userextended', 'Security verification failed. Please try again.');
		}

		if ($model->hasErrors('captcha')) {
			return Yii::t('userextended', 'Captcha verification is required.');
		}

		return Yii::t('userextended', 'Incorrect Username or Password');
	}

	/**
	 * Machine-readable fail reason (no secrets).
	 *
	 * @param LoginForm $model
	 * @param LoginRateLimiter $limiter
	 *
	 * @return string
	 */
	protected function resolveLoginFailureReason(LoginForm $model, LoginRateLimiter $limiter): string
	{
		if ($limiter->isLocked($model->login)) {
			return 'locked';
		}
		if ($model->hasErrors('turnstileToken')) {
			return 'turnstile';
		}
		if ($model->hasErrors('captcha')) {
			return 'captcha';
		}

		return 'credentials';
	}

	/**
	 * Logs the user out (destroys session + clears identity cookie via switchIdentity).
	 *
	 * @return Response
	 */
	public function actionLogout()
	{
		$identity = Yii::$app->user->identity;
		$userId = $identity ? (int) $identity->getId() : 0;

		if ($identity !== null) {
			$event = $this->getUserEvent($identity);
			$this->trigger(self::EVENT_BEFORE_LOGOUT, $event);
		}

		// logout(true): switchIdentity regenerates session id then destroys the session
		Yii::$app->getUser()->logout(true);

		if ($userId > 0) {
			SecurityAudit::log('logout', $userId, [
				'user_id' => $userId,
			], 'auth', 'User', '/user/security/logout');
		}

		if ($identity !== null) {
			$this->trigger(self::EVENT_AFTER_LOGOUT, $event);
		}

		return $this->goHome();
	}

	protected function regenerateSessionIdIfEnabled(): void
	{
		SessionHelper::regenerateIdIfEnabled();
	}
}

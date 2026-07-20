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
use cinghie\userextended\models\LoginForm;
use dektrium\user\controllers\SecurityController as BaseController;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
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
		} else {
			Yii::$app->session->setFlash('login', Yii::t('userextended', 'Type your credentials'));
		}

		if ($model->load(Yii::$app->getRequest()->post())) {
			$wasLocked = $limiter->isLocked($model->login);

			if ($model->login()) {
				$limiter->clear($model->login);
				Yii::$app->session->setFlash('login', Yii::t('userextended', 'Login successful'));
				$this->trigger(self::EVENT_AFTER_LOGIN, $event);
				return $this->goBack();
			}

			if ($limiter->isEnabled() && !$wasLocked && $model->shouldCountAsLoginFailure()) {
				$limiter->recordFailure($model->login);
				$limiter->applyDelay($model->login);
			}

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
	protected function resolveLoginFailureFlash(LoginForm $model, LoginRateLimiter $limiter)
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
}

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
use cinghie\userextended\helpers\ProfileAvatarService;
use cinghie\userextended\helpers\SecurityAudit;
use cinghie\userextended\models\Profile;
use cinghie\userextended\models\SettingsForm;
use dektrium\user\controllers\SettingsController as BaseController;
use dektrium\user\Module;
use yii\base\Exception;
use yii\base\ExitException;
use yii\base\InvalidArgumentException;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class SettingsController
 */
class SettingsController extends BaseController
{
	/**
	 * @inheritdoc
	 * Keeps Dektrium VerbFilter (disconnect/delete POST) + CSRF on ActiveForm posts.
	 */
	public function behaviors()
	{
		$behaviors = parent::behaviors();
		$behaviors['verbs'] = [
			'class' => VerbFilter::class,
			'actions' => [
				'disconnect' => ['POST'],
				'delete' => ['POST'],
			],
		];

		return $behaviors;
	}

	/**
	 * Shows profile settings form.
	 *
	 * @return string|Response
	 * @throws Exception
	 * @throws ExitException
	 * @throws InvalidCallException
	 * @throws InvalidConfigException
	 * @throws InvalidArgumentException
	 */
	public function actionProfile()
	{
		// Load Model
		$model = $this->finder->findProfileById(Yii::$app->user->identity->getId());

		// If Profile not exist create it
		if ($model === null) {
			$model = Yii::createObject(Profile::class);
			$model->link('user', Yii::$app->user->identity);
		}

		$model->scenario = 'update';

		// Profile Event
		$event = $this->getProfileEvent($model);

		// Ajax Validation (before avatar upload — avoids orphan files on AJAX validate)
		$this->performAjaxValidation($model);

		$avatarUpdate = ProfileAvatarService::begin($model);

		$this->trigger(self::EVENT_BEFORE_PROFILE_UPDATE, $event);

		if ($model->load(Yii::$app->request->post())) {
			$avatarUpdate->applyAfterLoad();

			if ($model->save()) {
				$avatarUpdate->finalizeAfterSave();

				SecurityAudit::log('profile_update', (int) Yii::$app->user->id, [
					'user_id' => (int) Yii::$app->user->id,
				], 'settings', 'Profile', '/user/settings/profile');

				Yii::$app->getSession()->setFlash('success', Yii::t('user', 'Your profile has been updated'));

				$this->trigger(self::EVENT_AFTER_PROFILE_UPDATE, $event);

				return $this->refresh();
			}

			$avatarUpdate->rollbackFailedSave();
		}

		return $this->render('profile', [
			'model' => $model,
		]);
	}

	/**
	 * @inheritdoc
	 * Account settings save audits password/email/username via SettingsForm::save().
	 */
	public function actionAccount()
	{
		/** @var SettingsForm $model */
		$model = Yii::createObject(SettingsForm::class);
		$event = $this->getFormEvent($model);

		$this->performAjaxValidation($model);

		$this->trigger(self::EVENT_BEFORE_ACCOUNT_UPDATE, $event);
		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			Yii::$app->session->setFlash('success', Yii::t('user', 'Your account details have been updated'));
			$this->trigger(self::EVENT_AFTER_ACCOUNT_UPDATE, $event);

			return $this->refresh();
		}

		return $this->render('account', [
			'model' => $model,
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function actionConfirm($id, $code)
	{
		$user = $this->finder->findUserById($id);

		if ($user === null || $this->module->emailChangeStrategy == Module::STRATEGY_INSECURE) {
			throw new NotFoundHttpException();
		}

		$event = $this->getUserEvent($user);
		$emailBefore = (string) $user->email;
		$flagsBefore = (int) $user->flags;

		$this->trigger(self::EVENT_BEFORE_CONFIRM, $event);
		$user->attemptEmailChange($code);
		$this->trigger(self::EVENT_AFTER_CONFIRM, $event);

		$emailAfter = (string) $user->email;
		$flagsAfter = (int) $user->flags;
		$completed = $emailBefore !== $emailAfter;
		// Only audit successful progress (token accepted / email finalized), not invalid tokens.
		if ($completed || $flagsBefore !== $flagsAfter) {
			SecurityAudit::log('email_confirm', (int) $user->id, [
				'user_id' => (int) $user->id,
				'email' => SecurityAudit::safeLogin($completed ? $emailAfter : (string) ($user->unconfirmed_email ?: $emailAfter)),
				'completed' => $completed,
			], 'settings', 'User', '/user/settings/confirm');
		}

		return $this->redirect(['account']);
	}

	/**
	 * @inheritdoc
	 */
	public function actionDisconnect($id)
	{
		$account = $this->finder->findAccount()->byId($id)->one();

		if ($account === null) {
			throw new NotFoundHttpException();
		}
		if ((int) $account->user_id !== (int) Yii::$app->user->id) {
			throw new ForbiddenHttpException();
		}

		$event = $this->getConnectEvent($account, $account->user);
		$provider = isset($account->provider) ? (string) $account->provider : '';

		$this->trigger(self::EVENT_BEFORE_DISCONNECT, $event);
		$account->delete();
		$this->trigger(self::EVENT_AFTER_DISCONNECT, $event);

		SecurityAudit::log('network_disconnect', (int) Yii::$app->user->id, [
			'user_id' => (int) Yii::$app->user->id,
			'provider' => mb_substr($provider, 0, 32),
		], 'settings', 'Account', '/user/settings/networks');

		return $this->redirect(['networks']);
	}

	/**
	 * @inheritdoc
	 */
	public function actionDelete()
	{
		if (!$this->module->enableAccountDelete) {
			throw new NotFoundHttpException(Yii::t('user', 'Not found'));
		}

		$user = Yii::$app->user->identity;
		$userId = (int) $user->id;
		$username = SecurityAudit::safeLogin(isset($user->username) ? $user->username : '');
		$event = $this->getUserEvent($user);

		Yii::$app->user->logout();

		$this->trigger(self::EVENT_BEFORE_DELETE, $event);
		$user->delete();
		$this->trigger(self::EVENT_AFTER_DELETE, $event);

		SecurityAudit::log('account_self_delete', $userId, [
			'user_id' => $userId,
			'username' => $username,
		], 'settings', 'User', '/user/settings/delete');

		Yii::$app->session->setFlash('info', Yii::t('user', 'Your account has been completely deleted'));

		return $this->goHome();
	}
}

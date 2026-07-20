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
use cinghie\userextended\helpers\RecoveryRateLimiter;
use cinghie\userextended\helpers\SecurityAudit;
use cinghie\userextended\models\RecoveryForm;
use dektrium\user\controllers\RecoveryController as BaseController;
use yii\web\NotFoundHttpException;

/**
 * Password recovery with IP/email throttle (use when enablePasswordRecovery is true).
 */
class RecoveryController extends BaseController
{
	/**
	 * @inheritdoc
	 * @throws NotFoundHttpException
	 */
	public function actionRequest()
	{
		if (!$this->module->enablePasswordRecovery) {
			throw new NotFoundHttpException();
		}

		/** @var RecoveryForm $model */
		$model = Yii::createObject([
			'class' => $this->module->modelMap['RecoveryForm'] ?? RecoveryForm::class,
			'scenario' => RecoveryForm::SCENARIO_REQUEST,
		]);
		$event = $this->getFormEvent($model);
		$limiter = RecoveryRateLimiter::create();

		$this->performAjaxValidation($model);
		$this->trigger(self::EVENT_BEFORE_REQUEST, $event);

		if ($model->load(Yii::$app->request->post())) {
			$email = $model->email;

			if ($limiter->isLocked($email)) {
				// Same generic wait message — do not reveal whether the account exists
				$model->addError(
					'email',
					Yii::t('userextended', 'Too many password recovery attempts. Please try again later.')
				);
				SecurityAudit::log('recovery_locked', 0, [
					'email' => SecurityAudit::safeLogin($email),
				], 'auth', 'User', '/user/recovery/request');
			} else {
				// Count after load; sendRecoveryMessage() validates and is anti-enumeration (always "success" UX)
				$sent = $model->sendRecoveryMessage();
				$limiter->recordAttempt($email);

				if ($sent) {
					$this->trigger(self::EVENT_AFTER_REQUEST, $event);
					SecurityAudit::log('recovery_request', 0, [
						'email' => SecurityAudit::safeLogin($email),
					], 'auth', 'User', '/user/recovery/request');

					return $this->render('/message', [
						'title' => Yii::t('user', 'Recovery message sent'),
						'module' => $this->module,
					]);
				}

				$limiter->applyDelay($email);
			}
		}

		return $this->render('request', [
			'model' => $model,
		]);
	}
}

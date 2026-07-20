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
use cinghie\userextended\helpers\RegistrationRateLimiter;
use cinghie\userextended\models\RegistrationForm;
use dektrium\user\controllers\RegistrationController as BaseController;
use dektrium\user\models\ResendForm;
use yii\base\InvalidConfigException;
use yii\web\NotFoundHttpException;

/**
 * Registration with IP/email throttle (captcha/Turnstile/terms stay on RegistrationForm).
 */
class RegistrationController extends BaseController
{
	/**
	 * @inheritdoc
	 * @throws InvalidConfigException
	 * @throws NotFoundHttpException
	 */
	public function actionRegister()
	{
		if (!$this->module->enableRegistration) {
			throw new NotFoundHttpException();
		}

		/** @var RegistrationForm $model */
		$modelClass = $this->module->modelMap['RegistrationForm'] ?? RegistrationForm::class;
		$model = Yii::createObject($modelClass);
		$event = $this->getFormEvent($model);
		$limiter = RegistrationRateLimiter::create();

		$this->trigger(self::EVENT_BEFORE_REGISTER, $event);
		$this->performAjaxValidation($model);

		if ($model->load(Yii::$app->request->post())) {
			if ($limiter->isLocked($model->email)) {
				$model->addError(
					'email',
					Yii::t('userextended', 'Too many registration attempts. Please try again later.')
				);
			} else {
				$registered = $model->register();
				$limiter->recordAttempt($model->email);

				if ($registered) {
					// Keep IP counter (mass-signup); clear only email key
					$limiter->clearEmail($model->email);
					$this->trigger(self::EVENT_AFTER_REGISTER, $event);

					return $this->render('/message', [
						'title' => Yii::t('user', 'Your account has been created'),
						'module' => $this->module,
					]);
				}

				$limiter->applyDelay($model->email);
			}
		}

		return $this->render('register', [
			'model' => $model,
			'module' => $this->module,
		]);
	}

	/**
	 * @inheritdoc
	 * Throttle confirmation resend (email spam).
	 *
	 * @throws InvalidConfigException
	 * @throws NotFoundHttpException
	 */
	public function actionResend()
	{
		if ($this->module->enableConfirmation == false) {
			throw new NotFoundHttpException();
		}

		/** @var ResendForm $model */
		$model = Yii::createObject(ResendForm::class);
		$event = $this->getFormEvent($model);
		$limiter = RegistrationRateLimiter::create();

		$this->trigger(self::EVENT_BEFORE_RESEND, $event);
		$this->performAjaxValidation($model);

		if ($model->load(Yii::$app->request->post())) {
			$email = $model->email;

			if ($limiter->isLocked($email)) {
				$model->addError(
					'email',
					Yii::t('userextended', 'Too many registration attempts. Please try again later.')
				);
			} elseif ($model->resend()) {
				$limiter->recordAttempt($email);
				$limiter->clearEmail($email);
				$this->trigger(self::EVENT_AFTER_RESEND, $event);

				return $this->render('/message', [
					'title' => Yii::t('user', 'A new confirmation link has been sent'),
					'module' => $this->module,
				]);
			} else {
				$limiter->recordAttempt($email);
				$limiter->applyDelay($email);
			}
		}

		return $this->render('resend', [
			'model' => $model,
		]);
	}
}

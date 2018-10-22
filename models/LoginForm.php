<?php

namespace cinghie\userextended\models;

use dektrium\user\models\LoginForm as BaseLoginForm;

class LoginForm extends BaseLoginForm
{
	/** @inheritdoc */
	public function rules()
	{
		$rules = [
			'loginTrim' => ['login', 'trim'],
			'requiredFields' => [['login'], 'required'],
			'confirmationValidate' => [
				'login',
				function ($attribute) {
					if ($this->user !== null) {
						$confirmationRequired = $this->module->enableConfirmation
							&& !$this->module->enableUnconfirmedLogin;
						if ($confirmationRequired && !$this->user->getIsConfirmed()) {
							$this->addError($attribute, Yii::t('user', 'You need to confirm your email address'));
						}
						if ($this->user->getIsBlocked()) {
							$this->addError($attribute, Yii::t('user', 'Your account has been blocked'));
						}
					}
				}
			],
			'rememberMe' => ['rememberMe', 'boolean'],
		];

		if (!$this->module->debug) {
			$rules = array_merge($rules, [
				'requiredFields' => [['login', 'password'], 'required'],
				'passwordValidate' => [
					'password',
					function ($attribute) {
						if ($this->user === null || !Password::validate($this->password, $this->user->password_hash)) {
							$this->addError($attribute, Yii::t('user', 'Invalid login or password'));
						}
					}
				]
			]);
		}

		return $rules;
	}
}

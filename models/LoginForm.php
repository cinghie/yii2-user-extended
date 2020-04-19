<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.2
 */

namespace cinghie\userextended\models;

use Yii;
use dektrium\user\models\LoginForm as BaseLoginForm;

/**
 * Class LoginForm
 */
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

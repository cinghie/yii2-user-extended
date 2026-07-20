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

namespace cinghie\userextended\models;

use Yii;
use cinghie\userextended\helpers\PasswordPolicy;
use dektrium\user\Module as UserModule;
use dektrium\user\models\SettingsForm as BaseSettingsForm;

/**
 * Account settings with password policy.
 *
 * Current password check uses Password::validate only.
 */
class SettingsForm extends BaseSettingsForm
{
	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = parent::rules();
		unset($rules['newPasswordLength']);

		$rules['newPasswordPolicy'] = PasswordPolicy::rule('new_password');
		$rules['newPasswordRequiredWhenExpired'] = [
			'new_password',
			'required',
			'when' => function () {
				$user = $this->user;
				return $user instanceof User && $user->isPasswordExpired();
			},
			'whenClient' => 'function () { return false; }',
			'message' => Yii::t('userextended', 'You must set a new password.'),
		];

		return $rules;
	}

	/**
	 * @inheritdoc
	 * Avoid assigning empty new_password (would not change hash, but keep intent clear).
	 */
	public function save()
	{
		if (!$this->validate()) {
			return false;
		}

		$this->user->scenario = 'settings';
		$this->user->username = $this->username;
		if ($this->new_password !== null && $this->new_password !== '') {
			$this->user->password = $this->new_password;
		}

		if ($this->email == $this->user->email && $this->user->unconfirmed_email != null) {
			$this->user->unconfirmed_email = null;
		} elseif ($this->email != $this->user->email) {
			switch ($this->module->emailChangeStrategy) {
				case UserModule::STRATEGY_INSECURE:
					$this->insecureEmailChange();
					break;
				case UserModule::STRATEGY_DEFAULT:
					$this->defaultEmailChange();
					break;
				case UserModule::STRATEGY_SECURE:
					$this->secureEmailChange();
					break;
				default:
					throw new \OutOfBoundsException('Invalid email changing strategy');
			}
		}

		return $this->user->save();
	}
}

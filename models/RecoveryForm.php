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

use cinghie\userextended\helpers\PasswordPolicy;
use dektrium\user\models\RecoveryForm as BaseRecoveryForm;

/**
 * Password recovery with policy checks (reset still uses Password::hash via User::resetPassword).
 */
class RecoveryForm extends BaseRecoveryForm
{
	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = parent::rules();
		unset($rules['passwordLength']);
		$rules['passwordPolicy'] = PasswordPolicy::rule('password');

		return $rules;
	}
}

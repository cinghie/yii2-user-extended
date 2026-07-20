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

namespace cinghie\userextended\helpers;

use yii\validators\Validator;

/**
 * Yii validator wrapping PasswordPolicy.
 */
class PasswordPolicyValidator extends Validator
{
	/**
	 * @inheritdoc
	 */
	public function validateAttribute($model, $attribute)
	{
		PasswordPolicy::validateModelAttribute($model, $attribute);
	}
}

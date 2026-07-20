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
use yii\db\ActiveRecord;

/**
 * RBAC assignment row (ActiveRecord for eager loading).
 *
 * @property string $item_name
 * @property string $user_id
 * @property int|null $created_at
 */
class AuthAssignment extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		$auth = Yii::$app->get('authManager', false);
		if ($auth !== null && !empty($auth->assignmentTable)) {
			return $auth->assignmentTable;
		}

		return '{{%auth_assignment}}';
	}
}

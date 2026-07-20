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

use yii\db\Migration;

/**
 * Indexes for admin user grid filters/sorts.
 *
 * Already present upstream:
 * - `user.username` / `user.email` unique indexes (dektrium yii2-user)
 * - `auth_assignment.user_id` index (Yii2 RBAC migrations)
 */
class m260720_111500_add_user_admin_indexes extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$this->createIndex('{{%idx-user-last_login_at}}', '{{%user}}', 'last_login_at');
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		$this->dropIndex('{{%idx-user-last_login_at}}', '{{%user}}');
	}
}

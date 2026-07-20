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
 * Track last password change for periodic password rotation.
 */
class m260720_160000_add_password_changed_at_to_user extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$this->addColumn('{{%user}}', 'password_changed_at', $this->integer()->null()->after('last_login_at'));

		// Existing accounts: treat registration time as last password change
		$this->update('{{%user}}', ['password_changed_at' => new \yii\db\Expression('[[created_at]]')]);
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		$this->dropColumn('{{%user}}', 'password_changed_at');
	}
}

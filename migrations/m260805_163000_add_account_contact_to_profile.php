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
 * Optional profile integer fields used when Module::$account / Module::$contact are enabled.
 */
class m260805_163000_add_account_contact_to_profile extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$table = \Yii::$app->db->schema->getTableSchema('{{%profile}}', true);
		if ($table === null) {
			return;
		}

		if (!isset($table->columns['account'])) {
			$this->addColumn('{{%profile}}', 'account', $this->integer()->null()->after('lastname'));
		}
		if (!isset($table->columns['contact'])) {
			$this->addColumn('{{%profile}}', 'contact', $this->integer()->null()->after('account'));
		}
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		$table = \Yii::$app->db->schema->getTableSchema('{{%profile}}', true);
		if ($table === null) {
			return;
		}

		if (isset($table->columns['contact'])) {
			$this->dropColumn('{{%profile}}', 'contact');
		}
		if (isset($table->columns['account'])) {
			$this->dropColumn('{{%profile}}', 'account');
		}
	}
}

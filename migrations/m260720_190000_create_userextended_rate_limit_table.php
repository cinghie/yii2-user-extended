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
 * Persist login/registration rate-limit counters so lockouts survive cache flush.
 */
class m260720_190000_create_userextended_rate_limit_table extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$table = '{{%userextended_rate_limit}}';
		if ($this->db->getTableSchema($table, true) !== null) {
			return true;
		}

		$this->createTable($table, [
			'rate_key' => $this->string(191)->notNull(),
			'attempt_count' => $this->integer()->notNull()->defaultValue(0),
			'locked_until' => $this->integer()->null(),
			'expires_at' => $this->integer()->notNull(),
			'updated_at' => $this->integer()->notNull(),
			'PRIMARY KEY ([[rate_key]])',
		]);

		$this->createIndex(
			'idx_userextended_rate_limit_expires',
			$table,
			'expires_at'
		);
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		$this->dropTable('{{%userextended_rate_limit}}');
	}
}

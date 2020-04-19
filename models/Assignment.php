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
use dektrium\rbac\models\Assignment as BaseAssignment;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\Connection;

/**
 * Class Assignment
 */
class Assignment extends BaseAssignment
{

	/**
	 * @inheritdoc
	 *
	 * @return object
	 * @throws InvalidConfigException
	 */
	public static function find()
	{
		return Yii::createObject(ActiveQuery::class, [static::class]);
	}

	/**
	 * Declares the name of the database table associated with this AR class.
	 * By default this method returns the class name as the table name by calling [[Inflector::camel2id()]]
	 * with prefix [[Connection::tablePrefix]]. For example if [[Connection::tablePrefix]] is 'tbl_',
	 * 'Customer' becomes 'tbl_customer', and 'OrderItem' becomes 'tbl_order_item'. You may override this method
	 * if the table is not named after this convention.
	 *
	 * @return string the table name
	 */
	public static function tableName()
	{
		return '{{%auth_assignment}}';
	}

	/**
	 * Returns the database connection used by this AR class.
	 * By default, the "db" application component is used as the database connection.
	 * You may override this method if you want to use a different database connection.
	 *
	 * @return Connection the database connection used by this AR class.
	 */
	public static function getDb()
	{
		return Yii::$app->getDb();
	}
}

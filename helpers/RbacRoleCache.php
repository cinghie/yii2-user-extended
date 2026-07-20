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

use Yii;
use cinghie\userextended\helpers\ModuleConfig;
use yii\caching\TagDependency;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * Cached RBAC role name list (for admin filters).
 */
class RbacRoleCache
{
	const CACHE_KEY = 'userextended.rbac.roleNames';
	const CACHE_TAG = 'userextended.rbac.roles';

	/**
	 * @return array name => name
	 */
	public static function getRoleNames()
	{
		$module = ModuleConfig::module();
		if (empty($module->enableRbacRoleCache) || !Yii::$app->has('cache')) {
			return self::fetchRoleNames();
		}

		$duration = max(0, (int) $module->rbacRoleCacheDuration);

		return Yii::$app->cache->getOrSet(
			self::CACHE_KEY,
			static function () {
				return self::fetchRoleNames();
			},
			$duration,
			new TagDependency(['tags' => [self::CACHE_TAG]])
		);
	}

	/**
	 * Invalidate cached role names (call after role create/update/delete).
	 *
	 * @return void
	 */
	public static function invalidate()
	{
		if (!Yii::$app->has('cache')) {
			return;
		}

		TagDependency::invalidate(Yii::$app->cache, [self::CACHE_TAG]);
		Yii::$app->cache->delete(self::CACHE_KEY);
	}

	/**
	 * @return array
	 */
	protected static function fetchRoleNames()
	{
		$itemTable = Yii::$app->authManager->itemTable;

		$rows = (new Query())
			->select(['name'])
			->from($itemTable)
			->andWhere(['type' => 1])
			->andWhere(['!=', 'name', 'public'])
			->all();

		return ArrayHelper::map($rows, 'name', 'name');
	}
}

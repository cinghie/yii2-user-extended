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

namespace cinghie\userextended\controllers;

use cinghie\userextended\helpers\RbacRoleCache;
use dektrium\rbac\controllers\RoleController as BaseRoleController;
use yii\web\Response;

/**
 * Role CRUD with RBAC role-list cache invalidation.
 */
class RoleController extends BaseRoleController
{
	/**
	 * @inheritdoc
	 */
	public function actionCreate()
	{
		return $this->invalidateOnRedirect(parent::actionCreate());
	}

	/**
	 * @inheritdoc
	 */
	public function actionUpdate($name)
	{
		return $this->invalidateOnRedirect(parent::actionUpdate($name));
	}

	/**
	 * @inheritdoc
	 */
	public function actionDelete($name)
	{
		$result = parent::actionDelete($name);
		RbacRoleCache::invalidate();

		return $result;
	}

	/**
	 * @param string|Response $result
	 *
	 * @return string|Response
	 */
	protected function invalidateOnRedirect($result)
	{
		if ($result instanceof Response) {
			RbacRoleCache::invalidate();
		}

		return $result;
	}
}

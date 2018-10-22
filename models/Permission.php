<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.7.0
 */

namespace cinghie\userextended\models;

use cinghie\traits\ViewsHelpersTrait;
use dektrium\rbac\models\Permission as BasePermission;

class Permission extends BasePermission
{
	use ViewsHelpersTrait;
}

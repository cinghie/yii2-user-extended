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

/**
 * Session-related helpers for auth hardening.
 */
final class SessionHelper
{
	/**
	 * Regenerate the PHP session id when the module enables it (e.g. after login).
	 */
	public static function regenerateIdIfEnabled(): void
	{
		$module = Yii::$app->getModule('userextended');
		if (!$module || empty($module->regenerateSessionId) || !Yii::$app->has('session')) {
			return;
		}

		$session = Yii::$app->getSession();
		if ($session->getIsActive()) {
			$session->regenerateID(true);
		}
	}
}

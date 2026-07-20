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

use dektrium\user\Module as UserModule;

/**
 * Applies safer Dektrium `user` module defaults from userextended (recovery/registration hardening).
 */
final class UserModuleHardening
{
	/**
	 * @param UserModule|\yii\base\Module $userModule
	 * @param \cinghie\userextended\Module $ue
	 */
	public static function apply($userModule, $ue): void
	{
		if (!is_object($userModule)) {
			return;
		}

		if (property_exists($userModule, 'recoverWithin')) {
			$userModule->recoverWithin = max(60, (int) $ue->recoverWithin);
		}
		if (property_exists($userModule, 'confirmWithin')) {
			$userModule->confirmWithin = max(60, (int) $ue->confirmWithin);
		}

		if (property_exists($userModule, 'emailChangeStrategy') && $ue->enableSecureEmailChange) {
			$userModule->emailChangeStrategy = UserModule::STRATEGY_SECURE;
		}

		// Never email plaintext generated passwords unless explicitly opted in
		if (property_exists($userModule, 'enableGeneratingPassword') && !$ue->mailPlaintextPasswords) {
			$userModule->enableGeneratingPassword = false;
		}
	}
}

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
use dektrium\user\helpers\Password;
use dektrium\user\models\User as BaseUser;
use yii\db\ActiveRecord;

/**
 * bcrypt cost helpers: raise Dektrium default (10) without breaking old hashes.
 *
 * Verification always uses password_verify (cost embedded in the hash).
 * New hashes use Module::$passwordHashCost via user module `cost`.
 * Optional transparent rehash on successful login (upgrade-only).
 */
final class PasswordHashCost
{
	public const DEFAULT_COST = 13;

	/** Floor for userextended config (never allow weaker than 12). */
	public const MIN_COST = 12;

	public const MAX_COST = 15;

	/**
	 * Target bcrypt cost from userextended (falls back to DEFAULT_COST).
	 */
	public static function targetCost(): int
	{
		try {
			$cost = (int) ModuleConfig::get('passwordHashCost', self::DEFAULT_COST);
		} catch (\Throwable $e) {
			$cost = self::DEFAULT_COST;
		}

		return self::clamp($cost);
	}

	public static function clamp(int $cost): int
	{
		if ($cost < self::MIN_COST) {
			return self::MIN_COST;
		}
		if ($cost > self::MAX_COST) {
			return self::MAX_COST;
		}

		return $cost;
	}

	/**
	 * Extract bcrypt cost from a hash, or null if not a recognizable bcrypt hash.
	 */
	public static function costFromHash(?string $hash): ?int
	{
		if ($hash === null || $hash === '') {
			return null;
		}

		if (preg_match('/^\$2[axy]\$(\d{2})\$/', $hash, $m)) {
			return (int) $m[1];
		}

		return null;
	}

	/**
	 * Whether an existing hash should be upgraded to the target cost.
	 * Only true when the stored cost is strictly below the target (never "downgrade").
	 */
	public static function needsRehash(?string $hash, ?int $cost = null): bool
	{
		if ($hash === null || $hash === '') {
			return false;
		}

		$cost = $cost ?? self::targetCost();
		$current = self::costFromHash($hash);
		if ($current === null) {
			return false;
		}

		return $current < $cost;
	}

	/**
	 * Apply userextended cost onto Dektrium user module (Password::hash reads user.cost).
	 * Never lowers an already higher cost set on the user module.
	 *
	 * @param \yii\base\Module $userModule
	 */
	public static function applyToUserModule($userModule): void
	{
		if (!is_object($userModule) || !property_exists($userModule, 'cost')) {
			return;
		}

		$target = self::targetCost();
		$current = (int) $userModule->cost;
		if ($current < $target) {
			$userModule->cost = $target;
		}
	}

	/**
	 * Rehash and persist if the stored hash uses a weaker cost.
	 * Does not bump password_changed_at (same password, stronger hash only).
	 *
	 * Security: re-validates the plaintext against the current hash; ensures
	 * Dektrium cost is raised before hashing; refuses to save a weaker/equal hash.
	 *
	 * @param ActiveRecord|BaseUser $user
	 */
	public static function rehashIfNeeded($user, string $plainPassword): bool
	{
		if ($plainPassword === '' || !is_object($user) || !method_exists($user, 'getAttribute')) {
			return false;
		}

		try {
			if (!ModuleConfig::get('rehashPasswordOnLogin', true)) {
				return false;
			}
		} catch (\Throwable $e) {
			return false;
		}

		$hash = (string) $user->getAttribute('password_hash');
		$target = self::targetCost();
		if (!self::needsRehash($hash, $target)) {
			return false;
		}

		// Defense in depth: never rehash unless plaintext matches the stored hash
		try {
			if (!Password::validate($plainPassword, $hash)) {
				return false;
			}
		} catch (\Throwable $e) {
			return false;
		}

		try {
			if (Yii::$app->hasModule('user')) {
				self::applyToUserModule(Yii::$app->getModule('user'));
			}

			$newHash = Password::hash($plainPassword);
			$newCost = self::costFromHash($newHash);
			// Refuse downgrade or no-op writes
			if ($newCost === null || $newCost < $target) {
				Yii::warning(
					'Refusing password rehash: new hash cost ' . var_export($newCost, true) . ' < target ' . $target,
					__METHOD__
				);
				return false;
			}

			$user->updateAttributes(['password_hash' => $newHash]);

			SecurityAudit::log('password_rehash', (int) $user->getAttribute('id'), [
				'from_cost' => self::costFromHash($hash),
				'to_cost' => $newCost,
			], 'auth', 'User', '/user/security/login');

			return true;
		} catch (\Throwable $e) {
			Yii::warning($e->getMessage(), __METHOD__);
			return false;
		}
	}
}

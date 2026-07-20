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
use cinghie\userextended\Module;
use yii\base\InvalidConfigException;

/**
 * Request-scoped access to userextended module settings.
 */
class ModuleConfig
{
	/** @var Module|null */
	private static $module;

	/** @var array<string, mixed> */
	private static $values = [];

	/**
	 * @return Module
	 * @throws InvalidConfigException
	 */
	public static function module()
	{
		if (self::$module === null) {
			$module = Yii::$app->getModule('userextended');
			if (!$module instanceof Module) {
				throw new InvalidConfigException('Module "userextended" is not configured.');
			}
			self::$module = $module;
		}

		return self::$module;
	}

	/**
	 * Read a module property once per request.
	 *
	 * @param string $name
	 * @param mixed $default
	 *
	 * @return mixed
	 * @throws InvalidConfigException
	 */
	public static function get($name, $default = null)
	{
		if (!array_key_exists($name, self::$values)) {
			$module = self::module();
			self::$values[$name] = property_exists($module, $name) ? $module->$name : $default;
		}

		return self::$values[$name];
	}

	/**
	 * Frequently used flags/paths, built once per request.
	 *
	 * @return array
	 * @throws InvalidConfigException
	 */
	public static function snapshot()
	{
		return self::getRuntime('snapshot', static function () {
			$m = self::module();
			return [
				'avatar' => (bool) $m->avatar,
				'avatarPath' => $m->avatarPath,
				'avatarURL' => $m->avatarURL,
				'avatarAllowedExtensions' => $m->avatarAllowedExtensions,
				'avatarMaxSize' => (int) $m->avatarMaxSize,
				'firstname' => (bool) $m->firstname,
				'lastname' => (bool) $m->lastname,
				'birthday' => (bool) $m->birthday,
				'signature' => (bool) $m->signature,
				'signatureAllowHtml' => (bool) $m->signatureAllowHtml,
				'signatureAllowedHtml' => $m->signatureAllowedHtml,
				'captcha' => (bool) $m->captcha,
				'sessionTimeout' => (int) $m->sessionTimeout,
				'enableLoginRateLimit' => (bool) $m->enableLoginRateLimit,
				'enableRbacRoleCache' => (bool) $m->enableRbacRoleCache,
			];
		});
	}

	/**
	 * @param string $key
	 * @param callable $factory
	 *
	 * @return mixed
	 */
	public static function getRuntime($key, callable $factory)
	{
		if (!array_key_exists($key, self::$values)) {
			self::$values[$key] = $factory();
		}

		return self::$values[$key];
	}

	/**
	 * Reset memoization (useful in long-running / tests).
	 *
	 * @return void
	 */
	public static function flush()
	{
		self::$module = null;
		self::$values = [];
	}
}

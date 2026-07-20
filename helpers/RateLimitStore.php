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
 * Cache-backed (or session fallback) storage for rate-limit counters.
 */
final class RateLimitStore
{
	/**
	 * @return array<string, mixed>|null
	 */
	public static function get(string $key): ?array
	{
		if (Yii::$app->has('cache')) {
			$data = Yii::$app->cache->get($key);
			return is_array($data) ? $data : null;
		}

		if (!Yii::$app->has('session')) {
			return null;
		}

		$data = Yii::$app->session->get($key);
		return is_array($data) ? $data : null;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function set(string $key, array $data, int $ttl): void
	{
		$ttl = max($ttl, 60);

		if (Yii::$app->has('cache')) {
			Yii::$app->cache->set($key, $data, $ttl);
			return;
		}

		if (!Yii::$app->has('session')) {
			return;
		}

		Yii::$app->session->set($key, $data);
	}

	public static function delete(string $key): void
	{
		if (Yii::$app->has('cache')) {
			Yii::$app->cache->delete($key);
			return;
		}

		if (!Yii::$app->has('session')) {
			return;
		}

		Yii::$app->session->remove($key);
	}
}

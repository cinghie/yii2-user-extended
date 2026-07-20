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
use yii\db\Query;

/**
 * Storage for login/registration rate-limit counters.
 *
 * Backends (Module::$rateLimitStorage):
 * - `db` (default): {{%userextended_rate_limit}} — survives cache flush
 * - `cache`: Yii cache, session fallback
 * - `auto`: prefer DB when the table exists, else cache
 */
final class RateLimitStore
{
	/** @var bool|null */
	private static $dbReady;

	/** @var bool */
	private static $missingTableWarned = false;

	/**
	 * Reset readiness flags (tests / long-running workers after migrate).
	 */
	public static function flushRuntime(): void
	{
		self::$dbReady = null;
		self::$missingTableWarned = false;
	}

	/**
	 * @return array{count:int,locked_until:?int}|null
	 */
	public static function get(string $key): ?array
	{
		if (self::useDb()) {
			return self::getFromDb($key);
		}

		return self::getFromCache($key);
	}

	/**
	 * @param array{count?:int,locked_until?:?int} $data
	 */
	public static function set(string $key, array $data, int $ttl): void
	{
		$ttl = max($ttl, 60);
		$payload = [
			'count' => max(0, (int) ($data['count'] ?? 0)),
			'locked_until' => isset($data['locked_until']) && $data['locked_until'] !== null
				? (int) $data['locked_until']
				: null,
		];

		if (self::useDb()) {
			self::setInDb($key, $payload, $ttl);
			return;
		}

		self::setInCache($key, $payload, $ttl);
	}

	public static function delete(string $key): void
	{
		if (self::useDb()) {
			self::deleteFromDb($key);
			return;
		}

		self::deleteFromCache($key);
	}

	private static function useDb(): bool
	{
		$mode = 'db';
		try {
			$mode = strtolower((string) ModuleConfig::get('rateLimitStorage', 'db'));
		} catch (\Throwable $e) {
			$mode = 'db';
		}

		if ($mode === 'cache') {
			return false;
		}

		// db + auto
		if (!self::isDbReady()) {
			if ($mode === 'db' && !self::$missingTableWarned) {
				self::$missingTableWarned = true;
				Yii::warning(
					'userextended rateLimitStorage=db but table {{%userextended_rate_limit}} is missing; falling back to cache. Run migrations.',
					__METHOD__
				);
			}
			return false;
		}

		return true;
	}

	private static function isDbReady(): bool
	{
		if (self::$dbReady !== null) {
			return self::$dbReady;
		}

		try {
			if (!Yii::$app->has('db')) {
				return self::$dbReady = false;
			}
			$schema = Yii::$app->db->getTableSchema('{{%userextended_rate_limit}}', true);
			return self::$dbReady = ($schema !== null);
		} catch (\Throwable $e) {
			return self::$dbReady = false;
		}
	}

	/**
	 * @return array{count:int,locked_until:?int}|null
	 */
	private static function getFromDb(string $key): ?array
	{
		try {
			$row = (new Query())
				->from('{{%userextended_rate_limit}}')
				->where(['rate_key' => $key])
				->one(Yii::$app->db);

			if ($row === false || $row === null) {
				return null;
			}

			$now = time();
			if ((int) $row['expires_at'] < $now) {
				self::deleteFromDb($key);
				return null;
			}

			$lockedUntil = $row['locked_until'] !== null && $row['locked_until'] !== ''
				? (int) $row['locked_until']
				: null;

			return [
				'count' => (int) $row['attempt_count'],
				'locked_until' => $lockedUntil,
			];
		} catch (\Throwable $e) {
			Yii::warning($e->getMessage(), __METHOD__);
			return self::getFromCache($key);
		}
	}

	/**
	 * @param array{count:int,locked_until:?int} $data
	 */
	private static function setInDb(string $key, array $data, int $ttl): void
	{
		$now = time();
		$expiresAt = $now + $ttl;
		if ($data['locked_until'] !== null && $data['locked_until'] > $expiresAt) {
			$expiresAt = (int) $data['locked_until'];
		}

		try {
			$db = Yii::$app->db;
			$values = [
				'attempt_count' => $data['count'],
				'locked_until' => $data['locked_until'],
				'expires_at' => $expiresAt,
				'updated_at' => $now,
			];

			// Upsert avoids duplicate-key races under concurrent failed logins
			$db->createCommand()->upsert(
				'{{%userextended_rate_limit}}',
				array_merge(['rate_key' => $key], $values),
				$values
			)->execute();

			self::maybeGarbageCollect($now);
		} catch (\Throwable $e) {
			Yii::warning($e->getMessage(), __METHOD__);
			self::setInCache($key, $data, $ttl);
		}
	}

	private static function deleteFromDb(string $key): void
	{
		try {
			Yii::$app->db->createCommand()
				->delete('{{%userextended_rate_limit}}', ['rate_key' => $key])
				->execute();
		} catch (\Throwable $e) {
			Yii::warning($e->getMessage(), __METHOD__);
			self::deleteFromCache($key);
		}
	}

	private static function maybeGarbageCollect(int $now): void
	{
		// ~1% chance: prune expired rows to keep the table small
		if (random_int(1, 100) !== 1) {
			return;
		}

		try {
			Yii::$app->db->createCommand()
				->delete('{{%userextended_rate_limit}}', ['<', 'expires_at', $now])
				->execute();
		} catch (\Throwable $e) {
			// ignore GC failures
		}
	}

	/**
	 * @return array{count:int,locked_until:?int}|null
	 */
	private static function getFromCache(string $key): ?array
	{
		if (Yii::$app->has('cache')) {
			$data = Yii::$app->cache->get($key);
			return self::normalizePayload($data);
		}

		if (!Yii::$app->has('session')) {
			return null;
		}

		return self::normalizePayload(Yii::$app->session->get($key));
	}

	/**
	 * @param array{count:int,locked_until:?int} $data
	 */
	private static function setInCache(string $key, array $data, int $ttl): void
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

	private static function deleteFromCache(string $key): void
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

	/**
	 * @param mixed $data
	 *
	 * @return array{count:int,locked_until:?int}|null
	 */
	private static function normalizePayload($data): ?array
	{
		if (!is_array($data)) {
			return null;
		}

		return [
			'count' => (int) ($data['count'] ?? 0),
			'locked_until' => isset($data['locked_until']) && $data['locked_until'] !== null
				? (int) $data['locked_until']
				: null,
		];
	}
}

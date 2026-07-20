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
 * Password-recovery request throttle (IP + email) via RateLimitStore.
 */
class RecoveryRateLimiter
{
	public const KEY_PREFIX = 'userextended.recover.';

	protected Module $module;

	/**
	 * @throws InvalidConfigException
	 */
	public function __construct(?Module $module = null)
	{
		$this->module = $module ?: ModuleConfig::module();
	}

	/**
	 * @throws InvalidConfigException
	 */
	public static function create(): self
	{
		return new self();
	}

	public function isEnabled(): bool
	{
		return (bool) $this->module->enableRecoveryRateLimit;
	}

	public function getClientIp(): string
	{
		$ip = Yii::$app->request->userIP;
		return is_string($ip) && $ip !== '' ? $ip : 'unknown';
	}

	/**
	 * @param string|null $email
	 */
	public function isLocked($email = null): bool
	{
		if (!$this->isEnabled()) {
			return false;
		}

		$now = time();
		foreach ($this->keys($email) as $key) {
			$data = RateLimitStore::get($key);
			if ($data !== null && !empty($data['locked_until']) && (int) $data['locked_until'] > $now) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string|null $email
	 */
	public function recordAttempt($email = null): void
	{
		if (!$this->isEnabled()) {
			return;
		}

		foreach ($this->keys($email) as $key) {
			$this->incrementKey($key);
		}
	}

	/**
	 * @param string|null $email
	 */
	public function applyDelay($email = null): void
	{
		if (!$this->isEnabled() || !$this->module->recoveryProgressiveDelay) {
			return;
		}

		$count = $this->getAttemptCount($email);
		if ($count <= 0) {
			return;
		}

		$base = max(0, (int) $this->module->recoveryDelayBaseSeconds);
		$max = max(0, (int) $this->module->recoveryDelayMaxSeconds);
		$delay = min($base * $count, $max);
		if ($delay > 0) {
			sleep($delay);
		}
	}

	/**
	 * @param string|null $email
	 */
	public function getAttemptCount($email = null): int
	{
		$count = 0;
		foreach ($this->keys($email) as $key) {
			$data = RateLimitStore::get($key);
			if ($data !== null) {
				$count = max($count, (int) ($data['count'] ?? 0));
			}
		}

		return $count;
	}

	/**
	 * @param string|null $email
	 *
	 * @return string[]
	 */
	protected function keys($email = null): array
	{
		$keys = [self::KEY_PREFIX . 'ip.' . hash('sha256', $this->getClientIp())];
		$normalized = strtolower(trim((string) $email));
		if ($normalized !== '') {
			$keys[] = self::KEY_PREFIX . 'email.' . hash('sha256', $normalized);
		}

		return $keys;
	}

	protected function incrementKey(string $key): void
	{
		$data = RateLimitStore::get($key);
		if ($data === null) {
			$data = ['count' => 0, 'locked_until' => null];
		}

		$data['count'] = (int) $data['count'] + 1;
		$maxAttempts = max(1, (int) $this->module->recoveryMaxAttempts);
		if ($data['count'] >= $maxAttempts) {
			$lockout = max(1, (int) $this->module->recoveryLockoutDuration);
			$data['locked_until'] = time() + $lockout;
		}

		$ttl = max(
			(int) $this->module->recoveryAttemptWindow,
			(int) $this->module->recoveryLockoutDuration,
			60
		);
		RateLimitStore::set($key, $data, $ttl);
	}
}

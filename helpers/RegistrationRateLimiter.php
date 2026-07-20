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
 * Cache/session based registration throttle (IP + email).
 *
 * Counts every POST attempt (success or failure) to limit mass signup and spam.
 */
class RegistrationRateLimiter
{
	public const KEY_PREFIX = 'userextended.register.';

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

	/**
	 * @return bool
	 */
	public function isEnabled()
	{
		return (bool) $this->module->enableRegistrationRateLimit;
	}

	/**
	 * @return string
	 */
	public function getClientIp()
	{
		$ip = Yii::$app->request->userIP;
		return is_string($ip) && $ip !== '' ? $ip : 'unknown';
	}

	/**
	 * @param string|null $email
	 *
	 * @return bool
	 */
	public function isLocked($email = null)
	{
		if (!$this->isEnabled()) {
			return false;
		}

		$now = time();
		foreach ($this->keys($email) as $key) {
			$data = $this->getData($key);
			if ($data !== null && !empty($data['locked_until']) && (int) $data['locked_until'] > $now) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string|null $email
	 *
	 * @return int
	 */
	public function getRemainingLockSeconds($email = null)
	{
		if (!$this->isEnabled()) {
			return 0;
		}

		$now = time();
		$remaining = 0;
		foreach ($this->keys($email) as $key) {
			$data = $this->getData($key);
			if ($data !== null && !empty($data['locked_until'])) {
				$left = (int) $data['locked_until'] - $now;
				if ($left > $remaining) {
					$remaining = $left;
				}
			}
		}

		return $remaining;
	}

	/**
	 * Count a registration POST (valid or invalid).
	 *
	 * @param string|null $email
	 *
	 * @return void
	 */
	public function recordAttempt($email = null)
	{
		if (!$this->isEnabled()) {
			return;
		}

		foreach ($this->keys($email) as $key) {
			$this->incrementKey($key);
		}
	}

	/**
	 * Clear only the email counter (keep IP limit after successful signup).
	 *
	 * @param string|null $email
	 *
	 * @return void
	 */
	public function clearEmail($email = null)
	{
		if (!$this->isEnabled()) {
			return;
		}

		$normalized = $this->normalizeEmail($email);
		if ($normalized === '') {
			return;
		}

		$this->deleteData(self::KEY_PREFIX . 'email.' . $this->hash($normalized));
	}

	/**
	 * @param string|null $email
	 *
	 * @return void
	 */
	public function applyDelay($email = null)
	{
		if (!$this->isEnabled() || !$this->module->registrationProgressiveDelay) {
			return;
		}

		$count = $this->getAttemptCount($email);
		if ($count <= 0) {
			return;
		}

		$base = max(0, (int) $this->module->registrationDelayBaseSeconds);
		$max = max(0, (int) $this->module->registrationDelayMaxSeconds);
		$delay = min($base * $count, $max);
		if ($delay > 0) {
			sleep($delay);
		}
	}

	/**
	 * @param string|null $email
	 *
	 * @return int
	 */
	public function getAttemptCount($email = null)
	{
		$count = 0;
		foreach ($this->keys($email) as $key) {
			$data = $this->getData($key);
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
	protected function keys($email = null)
	{
		$keys = [self::KEY_PREFIX . 'ip.' . $this->hash($this->getClientIp())];

		$normalized = $this->normalizeEmail($email);
		if ($normalized !== '') {
			$keys[] = self::KEY_PREFIX . 'email.' . $this->hash($normalized);
		}

		return $keys;
	}

	/**
	 * @param string $key
	 *
	 * @return void
	 */
	protected function incrementKey($key)
	{
		$data = $this->getData($key);
		if ($data === null) {
			$data = [
				'count' => 0,
				'locked_until' => null,
			];
		}

		$data['count'] = (int) $data['count'] + 1;
		$maxAttempts = max(1, (int) $this->module->registrationMaxAttempts);
		if ($data['count'] >= $maxAttempts) {
			$lockout = max(1, (int) $this->module->registrationLockoutDuration);
			$data['locked_until'] = time() + $lockout;
		}

		$this->setData($key, $data);
	}

	/**
	 * @param string|null $email
	 *
	 * @return string
	 */
	protected function normalizeEmail($email)
	{
		return strtolower(trim((string) $email));
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	protected function hash($value)
	{
		return hash('sha256', $value);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	protected function getData(string $key): ?array
	{
		return RateLimitStore::get($key);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	protected function setData(string $key, array $data): void
	{
		$ttl = max(
			(int) $this->module->registrationAttemptWindow,
			(int) $this->module->registrationLockoutDuration,
			60
		);
		RateLimitStore::set($key, $data, $ttl);
	}

	protected function deleteData(string $key): void
	{
		RateLimitStore::delete($key);
	}
}

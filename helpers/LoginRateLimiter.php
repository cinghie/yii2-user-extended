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
 * Cache/session based login brute-force protection (IP + username/email).
 */
class LoginRateLimiter
{
	const KEY_PREFIX = 'userextended.login.';

	/** @var Module */
	protected $module;

	/**
	 * @param Module|null $module
	 *
	 * @throws InvalidConfigException
	 */
	public function __construct($module = null)
	{
		$this->module = $module ?: Yii::$app->getModule('userextended');
	}

	/**
	 * @return self
	 * @throws InvalidConfigException
	 */
	public static function create()
	{
		return new self();
	}

	/**
	 * @return bool
	 */
	public function isEnabled()
	{
		return (bool) $this->module->enableLoginRateLimit;
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
	 * @param string|null $login
	 *
	 * @return bool
	 */
	public function isLocked($login = null)
	{
		if (!$this->isEnabled()) {
			return false;
		}

		$now = time();
		foreach ($this->keys($login) as $key) {
			$data = $this->getData($key);
			if ($data !== null && !empty($data['locked_until']) && (int) $data['locked_until'] > $now) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string|null $login
	 *
	 * @return int Seconds remaining on the strongest lock (0 if unlocked)
	 */
	public function getRemainingLockSeconds($login = null)
	{
		if (!$this->isEnabled()) {
			return 0;
		}

		$now = time();
		$remaining = 0;
		foreach ($this->keys($login) as $key) {
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
	 * @param string|null $login
	 *
	 * @return bool
	 */
	public function requiresCaptcha($login = null)
	{
		if (!$this->isEnabled()) {
			return false;
		}

		$after = (int) $this->module->loginCaptchaAfterAttempts;
		if ($after <= 0) {
			return false;
		}

		return $this->getAttemptCount($login) >= $after;
	}

	/**
	 * @param string|null $login
	 *
	 * @return int
	 */
	public function getAttemptCount($login = null)
	{
		$count = 0;
		foreach ($this->keys($login) as $key) {
			$data = $this->getData($key);
			if ($data !== null) {
				$count = max($count, (int) ($data['count'] ?? 0));
			}
		}

		return $count;
	}

	/**
	 * @param string|null $login
	 *
	 * @return void
	 */
	public function recordFailure($login = null)
	{
		if (!$this->isEnabled()) {
			return;
		}

		foreach ($this->keys($login) as $key) {
			$this->incrementKey($key);
		}
	}

	/**
	 * @param string|null $login
	 *
	 * @return void
	 */
	public function clear($login = null)
	{
		if (!$this->isEnabled()) {
			return;
		}

		foreach ($this->keys($login) as $key) {
			$this->deleteData($key);
		}
	}

	/**
	 * Progressive delay based on current failure count.
	 *
	 * @param string|null $login
	 *
	 * @return void
	 */
	public function applyDelay($login = null)
	{
		if (!$this->isEnabled() || !$this->module->loginProgressiveDelay) {
			return;
		}

		$count = $this->getAttemptCount($login);
		if ($count <= 0) {
			return;
		}

		$base = max(0, (int) $this->module->loginDelayBaseSeconds);
		$max = max(0, (int) $this->module->loginDelayMaxSeconds);
		$delay = min($base * $count, $max);
		if ($delay > 0) {
			sleep($delay);
		}
	}

	/**
	 * @param string|null $login
	 *
	 * @return string[]
	 */
	protected function keys($login = null)
	{
		$keys = [self::KEY_PREFIX . 'ip.' . $this->hash($this->getClientIp())];

		$normalized = $this->normalizeLogin($login);
		if ($normalized !== '') {
			$keys[] = self::KEY_PREFIX . 'user.' . $this->hash($normalized);
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
		$maxAttempts = max(1, (int) $this->module->loginMaxAttempts);
		if ($data['count'] >= $maxAttempts) {
			$lockout = max(1, (int) $this->module->loginLockoutDuration);
			$data['locked_until'] = time() + $lockout;
		}

		$this->setData($key, $data);
	}

	/**
	 * @param string|null $login
	 *
	 * @return string
	 */
	protected function normalizeLogin($login)
	{
		return strtolower(trim((string) $login));
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
	 * @param string $key
	 *
	 * @return array|null
	 */
	protected function getData($key)
	{
		if (Yii::$app->has('cache')) {
			$data = Yii::$app->cache->get($key);
			return is_array($data) ? $data : null;
		}

		$session = Yii::$app->session;
		$data = $session->get($key);
		return is_array($data) ? $data : null;
	}

	/**
	 * @param string $key
	 * @param array $data
	 *
	 * @return void
	 */
	protected function setData($key, array $data)
	{
		$ttl = max(
			(int) $this->module->loginAttemptWindow,
			(int) $this->module->loginLockoutDuration,
			60
		);

		if (Yii::$app->has('cache')) {
			Yii::$app->cache->set($key, $data, $ttl);
			return;
		}

		Yii::$app->session->set($key, $data);
	}

	/**
	 * @param string $key
	 *
	 * @return void
	 */
	protected function deleteData($key)
	{
		if (Yii::$app->has('cache')) {
			Yii::$app->cache->delete($key);
			return;
		}

		Yii::$app->session->remove($key);
	}
}

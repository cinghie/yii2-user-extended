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

use cinghie\userextended\Module;
use yii\base\InvalidConfigException;

/**
 * Validates and normalizes userextended Module parameters; provides env security presets.
 */
final class ModuleSettings
{
	/**
	 * Recommended security defaults for merge in app config or via Module::$securityPreset.
	 *
	 * @param string $name `dev`|`prod`
	 *
	 * @return array<string, mixed>
	 * @throws InvalidConfigException
	 */
	public static function securityPreset(string $name): array
	{
		$name = strtolower(trim($name));
		if ($name === 'auto') {
			$name = (defined('YII_ENV_PROD') && YII_ENV_PROD) ? 'prod' : 'dev';
		}

		switch ($name) {
			case 'prod':
				return [
					'disableAutoLogin' => true,
					'hardenSessionCookies' => true,
					'regenerateSessionId' => true,
					'invalidateRememberMeOnAuthTimeout' => true,
					'enableClientSessionExpireRedirect' => true,
					'sessionTimeout' => 1800,
					'useAbsoluteAuthTimeout' => true,
					'clientWarningBeforeExpire' => 60,
					'enableLoginRateLimit' => true,
					'loginMaxAttempts' => 5,
					'loginCaptchaAfterAttempts' => 3,
					'loginProgressiveDelay' => true,
					'enableRegistrationRateLimit' => true,
					'registrationProgressiveDelay' => true,
					'enablePasswordPolicy' => true,
					'passwordRequireUppercase' => true,
					'passwordRequireLowercase' => true,
					'passwordRequireDigit' => true,
					'passwordBanCommon' => true,
					'blockSelfRoleAssignment' => true,
					'enableSecurityAudit' => true,
					'enableRbacAssignmentAudit' => true,
					'signatureAllowHtml' => false,
				];

			case 'dev':
				return [
					'disableAutoLogin' => true,
					'hardenSessionCookies' => true,
					'regenerateSessionId' => true,
					'invalidateRememberMeOnAuthTimeout' => true,
					'enableClientSessionExpireRedirect' => true,
					'sessionTimeout' => 7200,
					'useAbsoluteAuthTimeout' => false,
					'absoluteAuthTimeout' => 0,
					'loginProgressiveDelay' => false,
					'loginCaptchaAfterAttempts' => 0,
					'registrationProgressiveDelay' => false,
					'enableLoginRateLimit' => true,
					'enablePasswordPolicy' => true,
					'enableSecurityAudit' => true,
					// Turnstile stays off unless keys are configured
					'enableCloudflareTurnstile' => false,
				];

			default:
				throw new InvalidConfigException('Unknown userextended securityPreset "' . $name . '". Use auto, dev, or prod.');
		}
	}

	/**
	 * Stock property defaults used to detect whether the app overrode a preset key.
	 *
	 * @return array<string, mixed>
	 */
	public static function factoryDefaults(): array
	{
		return [
			'sessionTimeout' => 3600,
			'useAbsoluteAuthTimeout' => false,
			'absoluteAuthTimeout' => 0,
			'enableClientSessionExpireRedirect' => true,
			'clientWarningBeforeExpire' => 60,
			'clientWarningOnce' => true,
			'clientSessionHeartbeatInterval' => 0,
			'clientSessionHeartbeatUrl' => null,
			'disableAutoLogin' => true,
			'hardenSessionCookies' => true,
			'sessionCookieSecure' => null,
			'sessionSameSite' => null,
			'regenerateSessionId' => true,
			'invalidateRememberMeOnAuthTimeout' => true,
			'enableLoginRateLimit' => true,
			'rateLimitStorage' => 'db',
			'loginMaxAttempts' => 5,
			'loginAttemptWindow' => 900,
			'loginLockoutDuration' => 900,
			'loginProgressiveDelay' => true,
			'loginDelayBaseSeconds' => 1,
			'loginDelayMaxSeconds' => 5,
			'loginCaptchaAfterAttempts' => 3,
			'enableCloudflareTurnstile' => false,
			'cloudflareTurnstileOnRegistration' => false,
			'cloudflareTurnstileTheme' => 'auto',
			'enableRegistrationRateLimit' => true,
			'registrationMaxAttempts' => 5,
			'registrationAttemptWindow' => 900,
			'registrationLockoutDuration' => 900,
			'registrationProgressiveDelay' => true,
			'registrationDelayBaseSeconds' => 1,
			'registrationDelayMaxSeconds' => 5,
			'enablePasswordPolicy' => true,
			'passwordMinLength' => 8,
			'passwordMaxLength' => 72,
			'passwordRequireUppercase' => true,
			'passwordRequireLowercase' => true,
			'passwordRequireDigit' => true,
			'passwordRequireSpecial' => false,
			'passwordBanCommon' => true,
			'passwordMaxAgeDays' => 0,
			'blockSelfRoleAssignment' => true,
			'enableRbacAssignmentAudit' => true,
			'enableSecurityAudit' => true,
			'signatureAllowHtml' => false,
			'avatarMaxSize' => 2097152,
			'rbacRoleCacheDuration' => 3600,
		];
	}

	/**
	 * Soft-apply a preset: only overwrite properties still at factory defaults.
	 *
	 * @throws InvalidConfigException
	 */
	public static function applySecurityPreset(Module $module): void
	{
		$presetName = $module->securityPreset;
		if ($presetName === null || $presetName === '') {
			return;
		}

		$preset = self::securityPreset((string) $presetName);
		$defaults = self::factoryDefaults();

		foreach ($preset as $key => $value) {
			if (!property_exists($module, $key)) {
				continue;
			}
			if (!array_key_exists($key, $defaults)) {
				$module->$key = $value;
				continue;
			}
			if ($module->$key === $defaults[$key]) {
				$module->$key = $value;
			}
		}
	}

	/**
	 * Clamp / coerce values and throw on impossible security config.
	 *
	 * @throws InvalidConfigException
	 */
	public static function validate(Module $module): void
	{
		$module->sessionTimeout = max(0, (int) $module->sessionTimeout);
		$module->absoluteAuthTimeout = max(0, (int) $module->absoluteAuthTimeout);
		$module->clientWarningBeforeExpire = max(0, (int) $module->clientWarningBeforeExpire);
		$module->clientSessionHeartbeatInterval = max(0, (int) $module->clientSessionHeartbeatInterval);
		$module->avatarMaxSize = max(1, (int) $module->avatarMaxSize);
		$module->rbacRoleCacheDuration = max(0, (int) $module->rbacRoleCacheDuration);

		$module->loginMaxAttempts = max(1, (int) $module->loginMaxAttempts);
		$module->loginAttemptWindow = max(1, (int) $module->loginAttemptWindow);
		$module->loginLockoutDuration = max(1, (int) $module->loginLockoutDuration);
		$module->loginDelayBaseSeconds = max(0, (int) $module->loginDelayBaseSeconds);
		$module->loginDelayMaxSeconds = max(0, (int) $module->loginDelayMaxSeconds);
		$module->loginCaptchaAfterAttempts = max(0, (int) $module->loginCaptchaAfterAttempts);

		$module->registrationMaxAttempts = max(1, (int) $module->registrationMaxAttempts);
		$module->registrationAttemptWindow = max(1, (int) $module->registrationAttemptWindow);
		$module->registrationLockoutDuration = max(1, (int) $module->registrationLockoutDuration);
		$module->registrationDelayBaseSeconds = max(0, (int) $module->registrationDelayBaseSeconds);
		$module->registrationDelayMaxSeconds = max(0, (int) $module->registrationDelayMaxSeconds);

		$module->passwordMinLength = max(1, (int) $module->passwordMinLength);
		$module->passwordMaxLength = max(1, (int) $module->passwordMaxLength);
		$module->passwordMaxAgeDays = max(0, (int) $module->passwordMaxAgeDays);

		if ($module->passwordMaxLength > 72) {
			$module->passwordMaxLength = 72;
		}
		if ($module->passwordMinLength > $module->passwordMaxLength) {
			throw new InvalidConfigException(
				'userextended.passwordMinLength must be <= passwordMaxLength.'
			);
		}

		if ($module->sessionTimeout > 1 && $module->clientWarningBeforeExpire >= $module->sessionTimeout) {
			$module->clientWarningBeforeExpire = $module->sessionTimeout - 1;
		}

		if ($module->loginDelayMaxSeconds < $module->loginDelayBaseSeconds) {
			$module->loginDelayMaxSeconds = $module->loginDelayBaseSeconds;
		}
		if ($module->registrationDelayMaxSeconds < $module->registrationDelayBaseSeconds) {
			$module->registrationDelayMaxSeconds = $module->registrationDelayBaseSeconds;
		}

		$ext = $module->avatarAllowedExtensions;
		if (!is_array($ext) || $ext === []) {
			throw new InvalidConfigException('userextended.avatarAllowedExtensions must be a non-empty array.');
		}
		$module->avatarAllowedExtensions = array_values(array_unique(array_map(
			static function ($e) {
				return strtolower(ltrim((string) $e, '.'));
			},
			$ext
		)));

		if (!is_string($module->avatarPath) || trim($module->avatarPath) === '') {
			throw new InvalidConfigException('userextended.avatarPath must be a non-empty string.');
		}
		if (!is_string($module->avatarURL) || trim($module->avatarURL) === '') {
			throw new InvalidConfigException('userextended.avatarURL must be a non-empty string.');
		}

		$sameSite = $module->sessionSameSite;
		if ($sameSite !== null && $sameSite !== '') {
			$normalized = ucfirst(strtolower((string) $sameSite));
			if (!in_array($normalized, ['Lax', 'Strict', 'None'], true)) {
				throw new InvalidConfigException(
					'userextended.sessionSameSite must be null, Lax, Strict, or None.'
				);
			}
			$module->sessionSameSite = $normalized;
		}

		$theme = strtolower((string) $module->cloudflareTurnstileTheme);
		if (!in_array($theme, ['auto', 'light', 'dark'], true)) {
			throw new InvalidConfigException(
				'userextended.cloudflareTurnstileTheme must be auto, light, or dark.'
			);
		}
		$module->cloudflareTurnstileTheme = $theme;

		$storage = strtolower(trim((string) $module->rateLimitStorage));
		if (!in_array($storage, ['db', 'cache', 'auto'], true)) {
			throw new InvalidConfigException(
				'userextended.rateLimitStorage must be db, cache, or auto.'
			);
		}
		$module->rateLimitStorage = $storage;

		if ($module->enableCloudflareTurnstile) {
			if (trim((string) $module->cloudflareSiteKey) === '' || trim((string) $module->cloudflareSecretKey) === '') {
				throw new InvalidConfigException(
					'userextended.enableCloudflareTurnstile requires cloudflareSiteKey and cloudflareSecretKey.'
				);
			}
		} elseif ($module->cloudflareTurnstileOnRegistration) {
			throw new InvalidConfigException(
				'userextended.cloudflareTurnstileOnRegistration requires enableCloudflareTurnstile = true.'
			);
		}

		if ($module->clientSessionHeartbeatUrl !== null && !is_string($module->clientSessionHeartbeatUrl)) {
			throw new InvalidConfigException('userextended.clientSessionHeartbeatUrl must be a string or null.');
		}

		if (!is_array($module->passwordCommonList)) {
			throw new InvalidConfigException('userextended.passwordCommonList must be an array.');
		}
	}
}

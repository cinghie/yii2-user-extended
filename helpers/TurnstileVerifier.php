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
use cinghie\userextended\helpers\ModuleConfig;
use yii\base\InvalidConfigException;

/**
 * Cloudflare Turnstile server-side verification (fail closed).
 */
class TurnstileVerifier
{
	const SITEVERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

	/**
	 * Enabled for login forms.
	 *
	 * @return bool
	 * @throws InvalidConfigException
	 */
	public static function isEnabledForLogin()
	{
		$module = ModuleConfig::module();
		return (bool) $module->enableCloudflareTurnstile;
	}

	/**
	 * Turnstile should be shown/enforced on login.
	 *
	 * @return bool
	 * @throws InvalidConfigException
	 */
	public static function shouldProtectLogin()
	{
		return self::isEnabledForLogin() && self::isConfigured();
	}

	/**
	 * Enabled for registration forms.
	 *
	 * @return bool
	 * @throws InvalidConfigException
	 */
	public static function isEnabledForRegistration()
	{
		$module = ModuleConfig::module();
		return (bool) $module->enableCloudflareTurnstile
			&& (bool) $module->cloudflareTurnstileOnRegistration;
	}

	/**
	 * True during Yii ActiveForm AJAX field validation (POST includes `ajax` form id).
	 * A forged XHR that submits the full form without that key is not treated as AJAX validation,
	 * so Turnstile still applies.
	 *
	 * @return bool
	 */
	public static function isActiveFormAjaxValidationRequest()
	{
		$request = Yii::$app->request;

		return $request->isAjax && $request->post('ajax') !== null && $request->post('ajax') !== '';
	}

	/**
	 * Site + secret keys present.
	 *
	 * @return bool
	 * @throws InvalidConfigException
	 */
	public static function isConfigured()
	{
		$module = ModuleConfig::module();
		$site = trim((string) $module->cloudflareSiteKey);
		$secret = trim((string) $module->cloudflareSecretKey);

		return $site !== '' && $secret !== '';
	}

	/**
	 * @return string
	 * @throws InvalidConfigException
	 */
	public static function getSiteKey()
	{
		return trim((string) ModuleConfig::module()->cloudflareSiteKey);
	}

	/**
	 * @return string
	 * @throws InvalidConfigException
	 */
	public static function getTheme()
	{
		$theme = strtolower(trim((string) ModuleConfig::module()->cloudflareTurnstileTheme));
		if (!in_array($theme, ['auto', 'light', 'dark'], true)) {
			return 'auto';
		}

		return $theme;
	}

	/**
	 * Verify Turnstile token with Cloudflare. Fail closed on any error.
	 *
	 * @param string|null $token
	 * @param string|null $remoteIp
	 *
	 * @return bool
	 */
	public static function verify($token, $remoteIp = null)
	{
		$token = is_string($token) ? trim($token) : '';
		if ($token === '') {
			return false;
		}

		try {
			if (!self::isConfigured()) {
				Yii::warning('Cloudflare Turnstile enabled but site/secret key missing.', __METHOD__);
				return false;
			}

			$secret = trim((string) ModuleConfig::module()->cloudflareSecretKey);
			$payload = [
				'secret' => $secret,
				'response' => $token,
			];

			if ($remoteIp === null) {
				$remoteIp = Yii::$app->request->userIP;
			}
			if (is_string($remoteIp) && $remoteIp !== '') {
				$payload['remoteip'] = $remoteIp;
			}

			$response = self::postSiteVerify($payload);
			if (!is_array($response) || empty($response['success'])) {
				return false;
			}

			return true;
		} catch (\Throwable $e) {
			Yii::error('Cloudflare Turnstile verification failed: ' . $e->getMessage(), __METHOD__);
			return false;
		}
	}

	/**
	 * Optional test/mock hook for siteverify HTTP (return decoded JSON array or null).
	 * Production code must leave this null.
	 *
	 * @var callable|null
	 */
	public static $siteVerifyHandler = null;

	/**
	 * @param array $payload
	 *
	 * @return array|null
	 */
	protected static function postSiteVerify(array $payload)
	{
		if (is_callable(self::$siteVerifyHandler)) {
			$result = call_user_func(self::$siteVerifyHandler, $payload);
			return is_array($result) ? $result : null;
		}

		// Prefer curl to avoid hard dependency on yii2-httpclient
		if (function_exists('curl_init')) {
			$ch = curl_init(self::SITEVERIFY_URL);
			curl_setopt_array($ch, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => http_build_query($payload),
				CURLOPT_TIMEOUT => 8,
				CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
			]);
			$body = curl_exec($ch);
			$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($body === false || $status < 200 || $status >= 300) {
				return null;
			}

			$data = json_decode($body, true);
			return is_array($data) ? $data : null;
		}

		$context = stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
				'content' => http_build_query($payload),
				'timeout' => 8,
				'ignore_errors' => true,
			],
		]);
		$body = @file_get_contents(self::SITEVERIFY_URL, false, $context);
		if ($body === false) {
			return null;
		}

		$data = json_decode($body, true);
		return is_array($data) ? $data : null;
	}
}

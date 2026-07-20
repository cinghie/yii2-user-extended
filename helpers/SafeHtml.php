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
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

/**
 * XSS-safe encoding / sanitization helpers for user-generated profile content.
 */
class SafeHtml
{
	/**
	 * Encode for HTML body/attribute output.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function encode($value)
	{
		return Html::encode((string) $value);
	}

	/**
	 * Strip all HTML (plain text storage).
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function plainText($value)
	{
		$text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

		return trim(preg_replace("/[ \t]+/u", ' ', str_replace(["\r\n", "\r"], "\n", $text)));
	}

	/**
	 * Purify HTML with a conservative whitelist.
	 *
	 * @param mixed $value
	 * @param string|null $allowedHtml HtmlPurifier HTML.Allowed string
	 *
	 * @return string
	 */
	public static function purify($value, $allowedHtml = null)
	{
		$content = (string) $value;
		if ($content === '') {
			return '';
		}

		if ($allowedHtml === null || $allowedHtml === '') {
			$allowedHtml = ModuleConfig::get(
				'signatureAllowedHtml',
				'p,br,strong,b,em,i,ul,ol,li,a[href|title|target|rel],span'
			);
		}

		return HtmlPurifier::process($content, function ($config) use ($allowedHtml) {
			/** @var \HTMLPurifier_Config $config */
			$config->set('HTML.Allowed', $allowedHtml);
			$config->set('Attr.AllowedFrameTargets', ['_blank']);
			$config->set('HTML.Nofollow', true);
			$config->set('URI.AllowedSchemes', [
				'http' => true,
				'https' => true,
				'mailto' => true,
			]);
		});
	}

	/**
	 * Sanitize signature according to module settings (plain text or purified HTML).
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function sanitizeSignature($value)
	{
		if (ModuleConfig::get('signatureAllowHtml')) {
			return self::purify($value);
		}

		return self::plainText($value);
	}

	/**
	 * Safe signature for HTML output.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function formatSignature($value)
	{
		if ($value === null || $value === '') {
			return '';
		}

		if (ModuleConfig::get('signatureAllowHtml')) {
			return self::purify($value);
		}

		return nl2br(self::encode(self::plainText($value)), false);
	}

	/**
	 * Safe bio for HTML output (always plain text).
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function formatBio($value)
	{
		if ($value === null || $value === '') {
			return '';
		}

		return nl2br(self::encode(self::plainText($value)), false);
	}

	/**
	 * Return a safe http(s) URL or null if scheme is not allowed.
	 *
	 * @param mixed $url
	 *
	 * @return string|null
	 */
	public static function safeHttpUrl($url)
	{
		$url = trim((string) $url);
		if ($url === '') {
			return null;
		}

		if (!preg_match('#^https?://#i', $url)) {
			$url = 'http://' . $url;
		}

		$parts = @parse_url($url);
		if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
			return null;
		}

		$scheme = strtolower($parts['scheme']);
		if ($scheme !== 'http' && $scheme !== 'https') {
			return null;
		}

		return $url;
	}
}

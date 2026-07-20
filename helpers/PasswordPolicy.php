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
use yii\base\Model;

/**
 * Configurable password strength / complexity policy.
 *
 * Hashing/verification must stay on dektrium\user\helpers\Password (Yii security).
 */
class PasswordPolicy
{
	/**
	 * Built-in common passwords (lowercase). Merged with module passwordCommonList.
	 *
	 * @var string[]
	 */
	private static $defaultCommon = [
		'password', 'password1', 'password123', '123456', '12345678', '123456789', '1234567890',
		'qwerty', 'qwerty123', 'abc123', 'admin', 'admin123', 'letmein', 'welcome', 'welcome1',
		'monkey', 'dragon', 'master', 'login', 'passw0rd', 'iloveyou', 'sunshine', 'princess',
		'football', 'baseball', 'soccer', 'hockey', 'shadow', 'trustno1', 'whatever', 'jordan',
		'superman', 'batman', 'michael', 'jennifer', 'hunter', 'buster', 'charlie',
		'aa123456', '1q2w3e4r', 'qwertyuiop', '000000', '111111', '123123', '654321', 'password!',
		'changeme', 'corima', 'corimacrm',
	];

	/**
	 * @return bool
	 */
	public static function isEnabled()
	{
		return (bool) ModuleConfig::get('enablePasswordPolicy', true);
	}

	/**
	 * Yii validator rule for an attribute.
	 *
	 * @param string $attribute
	 *
	 * @return array
	 */
	public static function rule($attribute)
	{
		return [
			$attribute,
			PasswordPolicyValidator::class,
			'skipOnEmpty' => true,
		];
	}

	/**
	 * @param Model $model
	 * @param string $attribute
	 *
	 * @return void
	 */
	public static function validateModelAttribute(Model $model, $attribute)
	{
		$password = (string) $model->$attribute;
		if ($password === '') {
			return;
		}

		foreach (self::check($password) as $message) {
			$model->addError($attribute, $message);
		}
	}

	/**
	 * @param string $password
	 *
	 * @return string[]
	 */
	public static function check($password)
	{
		$min = self::isEnabled()
			? max(1, (int) ModuleConfig::get('passwordMinLength', 8))
			: 6;
		$max = self::isEnabled()
			? max($min, (int) ModuleConfig::get('passwordMaxLength', 72))
			: 72;
		if ($max > 72) {
			$max = 72;
		}

		$errors = [];
		$len = mb_strlen($password, 'UTF-8');

		if ($len < $min) {
			$errors[] = Yii::t('userextended', 'Password must be at least {n} characters.', ['n' => $min]);
		}
		if ($len > $max) {
			$errors[] = Yii::t('userextended', 'Password must be at most {n} characters.', ['n' => $max]);
		}

		if (!self::isEnabled()) {
			return $errors;
		}

		if (ModuleConfig::get('passwordRequireUppercase', true) && !preg_match('/\p{Lu}/u', $password)) {
			$errors[] = Yii::t('userextended', 'Password must contain at least one uppercase letter.');
		}
		if (ModuleConfig::get('passwordRequireLowercase', true) && !preg_match('/\p{Ll}/u', $password)) {
			$errors[] = Yii::t('userextended', 'Password must contain at least one lowercase letter.');
		}
		if (ModuleConfig::get('passwordRequireDigit', true) && !preg_match('/\d/u', $password)) {
			$errors[] = Yii::t('userextended', 'Password must contain at least one digit.');
		}
		if (ModuleConfig::get('passwordRequireSpecial', false) && !preg_match('/[^\p{L}\p{N}]/u', $password)) {
			$errors[] = Yii::t('userextended', 'Password must contain at least one special character.');
		}

		if (ModuleConfig::get('passwordBanCommon', true) && self::isCommon($password)) {
			$errors[] = Yii::t('userextended', 'This password is too common. Please choose a stronger one.');
		}

		return $errors;
	}

	/**
	 * @param string $password
	 *
	 * @return bool
	 */
	public static function isCommon($password)
	{
		$needle = mb_strtolower($password, 'UTF-8');
		$extra = ModuleConfig::get('passwordCommonList', []);
		if (!is_array($extra)) {
			$extra = [];
		}
		$list = array_unique(array_merge(self::$defaultCommon, array_map('strtolower', $extra)));

		return in_array($needle, $list, true);
	}

	/**
	 * Generate a password that satisfies the current module policy.
	 * Prefer this over Password::generate() when enableGeneratingPassword / resendPassword run.
	 *
	 * @param int|null $length
	 *
	 * @return string
	 */
	public static function generate($length = null)
	{
		$min = max(1, (int) ModuleConfig::get('passwordMinLength', 8));
		$max = min(72, max($min, (int) ModuleConfig::get('passwordMaxLength', 72)));
		if ($length === null) {
			$length = max($min, 12);
		}
		$length = max($min, min((int) $length, $max));

		$sets = [];
		if (!self::isEnabled() || ModuleConfig::get('passwordRequireLowercase', true)) {
			$sets[] = 'abcdefghjkmnpqrstuvwxyz';
		}
		if (!self::isEnabled() || ModuleConfig::get('passwordRequireUppercase', true)) {
			$sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
		}
		if (!self::isEnabled() || ModuleConfig::get('passwordRequireDigit', true)) {
			$sets[] = '23456789';
		}
		if (self::isEnabled() && ModuleConfig::get('passwordRequireSpecial', false)) {
			$sets[] = '!@#$%^&*()-_=+[]{}';
		}
		if ($sets === []) {
			$sets = [
				'abcdefghjkmnpqrstuvwxyz',
				'ABCDEFGHJKMNPQRSTUVWXYZ',
				'23456789',
			];
		}

		for ($attempt = 0; $attempt < 30; $attempt++) {
			$password = self::generateFromSets($length, $sets);
			if (self::check($password) === []) {
				return $password;
			}
		}

		// Last resort: longer password from all sets (should still pass length/complexity)
		return self::generateFromSets(min($max, max($length, $min + 4)), $sets);
	}

	/**
	 * @param int $length
	 * @param string[] $sets
	 *
	 * @return string
	 */
	private static function generateFromSets($length, array $sets)
	{
		$all = '';
		$password = '';
		foreach ($sets as $set) {
			$password .= $set[random_int(0, strlen($set) - 1)];
			$all .= $set;
		}

		$allChars = str_split($all);
		$remaining = max(0, $length - count($sets));
		for ($i = 0; $i < $remaining; $i++) {
			$password .= $allChars[random_int(0, count($allChars) - 1)];
		}

		return self::shuffleString($password);
	}

	/**
	 * @param string $password
	 *
	 * @return string
	 */
	private static function shuffleString($password)
	{
		$chars = preg_split('//u', $password, -1, PREG_SPLIT_NO_EMPTY);
		for ($i = count($chars) - 1; $i > 0; $i--) {
			$j = random_int(0, $i);
			$tmp = $chars[$i];
			$chars[$i] = $chars[$j];
			$chars[$j] = $tmp;
		}

		return implode('', $chars);
	}
}

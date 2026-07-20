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
use yii\helpers\Json;

/**
 * Structured security audit trail (no passwords/tokens).
 *
 * Writes to cinghie logger when available, otherwise Yii::info category
 * `userextended.security`.
 */
class SecurityAudit
{
	/** @var string[] Actions gated by enableRbacAssignmentAudit */
	private static $rbacActions = [
		'assign_update',
		'self_escalation_block',
	];

	/** @var string[] Payload keys that must never be stored */
	private static $blockedKeys = [
		'password',
		'new_password',
		'current_password',
		'token',
		'turnstiletoken',
		'cf-turnstile-response',
		'auth_key',
		'authkey',
		'hash',
		'password_hash',
		'cookie',
		'csrf',
		'_csrf',
	];

	/**
	 * @param string $action Short action code (max 32)
	 * @param int $entityId Target entity id (0 when N/A)
	 * @param array $data Structured payload (no secrets)
	 * @param string $entityName
	 * @param string $entityModel
	 * @param string $entityUrl
	 *
	 * @return void
	 */
	public static function log(
		$action,
		$entityId = 0,
		array $data = [],
		$entityName = 'security',
		$entityModel = 'User',
		$entityUrl = '/user/security/login'
	) {
		$action = (string) $action;
		if (!self::isEnabled($action)) {
			return;
		}

		$data = self::sanitizeData($data);
		$actorId = Yii::$app->user->isGuest ? null : (int) Yii::$app->user->id;
		$ip = self::clientIp();
		$route = '';
		try {
			$route = (string) (Yii::$app->requestedRoute ?: '');
		} catch (\Throwable $e) {
			$route = '';
		}

		// Reserved keys always win over caller payload
		$context = array_merge($data, [
			'action' => $action,
			'entityId' => (int) $entityId,
			'actorId' => $actorId,
			'ip' => $ip,
			'route' => $route,
		]);

		$payload = Json::encode($context);

		if (class_exists(\cinghie\logger\models\Loggers::class)) {
			try {
				$logger = new \cinghie\logger\models\Loggers();
				$logger->entity_name = mb_substr((string) $entityName, 0, 32);
				$logger->entity_id = (int) $entityId;
				$logger->entity_code = 'userextended';
				$logger->entity_model = mb_substr((string) $entityModel, 0, 32);
				$logger->entity_url = mb_substr((string) $entityUrl, 0, 128);
				$logger->action = mb_substr($action, 0, 32);
				$logger->data = $payload;
				$logger->icon = 'fa fa-shield';
				$logger->ip = mb_substr($ip, 0, 16);
				$logger->created_by = $actorId;
				$logger->created = date('Y-m-d H:i:s');
				$logger->created_date = date('Y-m-d');
				$logger->created_time = date('H:i:s');
				$logger->save(false);
				return;
			} catch (\Throwable $e) {
				Yii::warning($e->getMessage(), 'userextended.security');
			}
		}

		Yii::info($context, 'userextended.security');
	}

	/**
	 * @param string $action
	 *
	 * @return bool
	 */
	public static function isEnabled($action = '')
	{
		try {
			if (!ModuleConfig::get('enableSecurityAudit', true)) {
				return false;
			}
			if (
				$action !== ''
				&& in_array($action, self::$rbacActions, true)
				&& !ModuleConfig::get('enableRbacAssignmentAudit', true)
			) {
				return false;
			}
		} catch (\Throwable $e) {
			// Module may be unavailable early in bootstrap — still allow Yii::info fallback
			return true;
		}

		return true;
	}

	/**
	 * Safe truncated login identifier (never a password).
	 *
	 * @param string|null $login
	 *
	 * @return string
	 */
	public static function safeLogin($login)
	{
		$login = strtolower(trim((string) $login));
		if ($login === '') {
			return '';
		}

		return mb_substr($login, 0, 190);
	}

	/**
	 * @return string
	 */
	public static function clientIp()
	{
		try {
			$ip = Yii::$app->request->userIP;
		} catch (\Throwable $e) {
			return '';
		}

		return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
	}

	/**
	 * Strip known secret keys from payloads (case-insensitive).
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected static function sanitizeData(array $data)
	{
		$clean = [];
		foreach ($data as $key => $value) {
			$normalized = strtolower(str_replace([' ', '-'], '_', (string) $key));
			if (in_array($normalized, self::$blockedKeys, true)) {
				continue;
			}
			if (is_array($value)) {
				$clean[$key] = self::sanitizeData($value);
				continue;
			}
			$clean[$key] = $value;
		}

		return $clean;
	}
}

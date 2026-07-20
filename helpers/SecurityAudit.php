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
 * Best-effort security audit trail (uses cinghie logger when available).
 */
class SecurityAudit
{
	/**
	 * @param string $action Short action code (max 32)
	 * @param int $entityId Target entity id
	 * @param array $data Structured payload (no secrets)
	 * @param string $entityName
	 * @param string $entityModel
	 *
	 * @return void
	 */
	public static function log($action, $entityId, array $data = [], $entityName = 'rbac_assignment', $entityModel = 'User')
	{
		if (!ModuleConfig::get('enableRbacAssignmentAudit', true)) {
			Yii::info([
				'action' => $action,
				'entityId' => $entityId,
				'data' => $data,
			], 'userextended.security');
			return;
		}

		$payload = Json::encode($data);
		$actorId = Yii::$app->user->isGuest ? null : (int) Yii::$app->user->id;
		$ip = Yii::$app->request->userIP;
		$ip = is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';

		if (class_exists(\cinghie\logger\models\Loggers::class)) {
			try {
				$logger = new \cinghie\logger\models\Loggers();
				$logger->entity_name = mb_substr((string) $entityName, 0, 32);
				$logger->entity_id = (int) $entityId;
				$logger->entity_code = 'userextended';
				$logger->entity_model = mb_substr((string) $entityModel, 0, 32);
				$logger->entity_url = '/user/admin/assignments';
				$logger->action = mb_substr((string) $action, 0, 32);
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

		Yii::info([
			'action' => $action,
			'entityId' => $entityId,
			'actorId' => $actorId,
			'ip' => $ip,
			'data' => $data,
		], 'userextended.security');
	}
}

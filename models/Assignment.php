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

namespace cinghie\userextended\models;

use Yii;
use cinghie\userextended\helpers\ModuleConfig;
use cinghie\userextended\helpers\SecurityAudit;
use dektrium\rbac\models\Assignment as BaseAssignment;

/**
 * RBAC assignment with self-escalation guard and audit trail.
 */
class Assignment extends BaseAssignment
{
	/**
	 * Only `items` may be mass-assigned from the form.
	 * `user_id` must stay the controller/URL target (never from POST).
	 *
	 * @inheritdoc
	 */
	public function safeAttributes()
	{
		return ['items'];
	}

	/**
	 * @inheritdoc
	 */
	public function updateAssignments()
	{
		if (!$this->validate()) {
			return false;
		}

		if (!is_array($this->items)) {
			$this->items = [];
		}

		$actorId = Yii::$app->user->isGuest ? 0 : (int) Yii::$app->user->id;
		$targetId = (int) $this->user_id;

		$assignedItems = $this->manager->getItemsByUser($this->user_id);
		$assignedItemsNames = array_keys($assignedItems);

		$toRevoke = array_values(array_diff($assignedItemsNames, $this->items));
		$toAssign = array_values(array_diff($this->items, $assignedItemsNames));

		if ($toRevoke === [] && $toAssign === []) {
			$this->updated = true;
			return true;
		}

		if (
			$actorId > 0
			&& $actorId === $targetId
			&& $toAssign !== []
			&& ModuleConfig::get('blockSelfRoleAssignment', true)
		) {
			$this->addError(
				'items',
				Yii::t('userextended', 'You cannot assign new roles or permissions to yourself.')
			);

			SecurityAudit::log('self_escalation_block', $targetId, [
				'actor_id' => $actorId,
				'attempted_add' => $toAssign,
				'current' => $assignedItemsNames,
			]);

			return false;
		}

		foreach ($toRevoke as $item) {
			$this->manager->revoke($assignedItems[$item], $this->user_id);
		}

		foreach ($toAssign as $item) {
			$authItem = $this->manager->getItem($item);
			if ($authItem !== null) {
				$this->manager->assign($authItem, $this->user_id);
			}
		}

		$this->updated = true;

		SecurityAudit::log('assign_update', $targetId, [
			'actor_id' => $actorId,
			'added' => $toAssign,
			'removed' => $toRevoke,
		]);

		return true;
	}
}

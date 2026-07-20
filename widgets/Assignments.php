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

namespace cinghie\userextended\widgets;

use Yii;
use cinghie\userextended\models\Assignment;
use dektrium\rbac\components\DbManager;
use yii\base\InvalidConfigException;
use yii\base\Widget;

/**
 * Assignments form using the secured Assignment model.
 *
 * Prefer handling POST in AdminController / AssignmentController and set
 * processPost=false so mutation is not done during widget render.
 */
class Assignments extends Widget
{
	/** @var int|null */
	public $userId;

	/** @var Assignment|null */
	public $model;

	/**
	 * When false, only renders the form (controller already processed POST).
	 *
	 * @var bool
	 */
	public $processPost = true;

	/** @var DbManager */
	protected $manager;

	/**
	 * @inheritdoc
	 * @throws InvalidConfigException
	 */
	public function init()
	{
		parent::init();
		$this->manager = Yii::$app->authManager;
		if ($this->model === null && $this->userId === null) {
			throw new InvalidConfigException('You should set ' . __CLASS__ . '::$userId or $model');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function run()
	{
		$model = $this->model;
		if ($model === null) {
			$model = Yii::createObject([
				'class' => Assignment::class,
				'user_id' => $this->userId,
			]);
		}

		if ($this->processPost && $model->load(Yii::$app->request->post())) {
			if ($this->userId !== null) {
				$model->user_id = $this->userId;
			}
			$model->updateAssignments();
		}

		return $this->render('@dektrium/rbac/widgets/views/form', [
			'model' => $model,
		]);
	}
}

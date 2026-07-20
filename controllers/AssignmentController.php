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

namespace cinghie\userextended\controllers;

use Yii;
use cinghie\userextended\models\Assignment;
use cinghie\userextended\widgets\Assignments;
use dektrium\rbac\controllers\AssignmentController as BaseController;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;

/**
 * Centralized RBAC assignment endpoint (CSRF + admin + secured Assignment model).
 */
class AssignmentController extends BaseController
{
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return array_merge(parent::behaviors(), [
			'access' => [
				'class' => AccessControl::class,
				'rules' => [
					[
						'allow' => true,
						'roles' => ['admin'],
					],
				],
			],
			'verbs' => [
				'class' => VerbFilter::class,
				'actions' => [
					'assign' => ['GET', 'POST'],
				],
			],
		]);
	}

	/**
	 * @inheritdoc
	 * @throws BadRequestHttpException
	 */
	public function actionAssign($id)
	{
		if (Yii::$app->request->isPost && !Yii::$app->request->validateCsrfToken()) {
			throw new BadRequestHttpException(Yii::t('yii', 'Unable to verify your data submission.'));
		}

		$targetUserId = (int) $id;
		$model = Yii::createObject([
			'class' => Assignment::class,
			'user_id' => $targetUserId,
		]);

		if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
			// Defense in depth: never trust posted user_id
			$model->user_id = $targetUserId;
			$model->updateAssignments();
		}

		return Assignments::widget([
			'userId' => $targetUserId,
			'model' => $model,
			'processPost' => false,
		]);
	}
}

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
use cinghie\userextended\helpers\ProfileAvatarService;
use cinghie\userextended\models\Profile;
use dektrium\user\controllers\SettingsController as BaseController;
use yii\base\Exception;
use yii\base\ExitException;
use yii\base\InvalidArgumentException;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * Class SettingsController
 */
class SettingsController extends BaseController
{
	/**
	 * @inheritdoc
	 * Keeps Dektrium VerbFilter (disconnect/delete POST) + CSRF on ActiveForm posts.
	 */
	public function behaviors()
	{
		$behaviors = parent::behaviors();
		$behaviors['verbs'] = [
			'class' => VerbFilter::class,
			'actions' => [
				'disconnect' => ['POST'],
				'delete' => ['POST'],
			],
		];

		return $behaviors;
	}

	/**
	 * Shows profile settings form.
	 *
	 * @return string|Response
	 * @throws Exception
	 * @throws ExitException
	 * @throws InvalidCallException
	 * @throws InvalidConfigException
	 * @throws InvalidArgumentException
	 */
    public function actionProfile()
    {
        // Load Model
        $model = $this->finder->findProfileById( Yii::$app->user->identity->getId() );

        // If Profile not exist create it
        if ($model === null) {
            $model = Yii::createObject(Profile::class);
            $model->link('user', Yii::$app->user->identity);
        }

        $model->scenario = 'update';

        // Profile Event
        $event = $this->getProfileEvent($model);

        // Ajax Validation (before avatar upload — avoids orphan files on AJAX validate)
        $this->performAjaxValidation($model);

        $avatarUpdate = ProfileAvatarService::begin($model);

        $this->trigger(self::EVENT_BEFORE_PROFILE_UPDATE, $event);

        if ($model->load(Yii::$app->request->post())) {
            $avatarUpdate->applyAfterLoad();

            if ($model->save()) {
                $avatarUpdate->finalizeAfterSave();

                Yii::$app->getSession()->setFlash('success', Yii::t('user', 'Your profile has been updated'));

                $this->trigger(self::EVENT_AFTER_PROFILE_UPDATE, $event);

                return $this->refresh();
            }

            $avatarUpdate->rollbackFailedSave();
        }

        return $this->render('profile', [
            'model' => $model
        ]);
    }
}

<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.1
 */

namespace cinghie\userextended\controllers;

use Yii;
use dektrium\user\controllers\SettingsController as BaseController;

class SettingsController extends BaseController
{

    /**
     * Shows profile settings form.
     *
     * @return string|\yii\web\Response
     */
    public function actionProfile()
    {
        // Load Model
        $model = $this->finder->findProfileById(\Yii::$app->user->identity->getId());

        // If Profile not exist create it
        if ($model == null) {
            $model = \Yii::createObject(Profile::className());
            $model->link('user', \Yii::$app->user->identity);
        }

        // Load Old Image
        $oldImage = $model->avatar;

        // Load avatarPath from Module Params
        $avatarPath = \Yii::getAlias(\Yii::$app->getModule('userextended')->avatarPath);

        // Create uploadAvatar Instance
        $image = $model->uploadAvatar($avatarPath);

        // Profile Event
        $event = $this->getProfileEvent($model);

        // Ajax Validation
        $this->performAjaxValidation($model);

        $this->trigger(self::EVENT_BEFORE_PROFILE_UPDATE, $event);

        if ($model->load(\Yii::$app->request->post()) && $model->save()) {

            // revert back if no valid file instance uploaded
            if ($image === false) {

                $model->avatar = $oldImage;

            } else {
                
                // if is there an old image, delete it
                if($oldImage) {
                    $model->deleteImage($oldImage);
                }

                // upload new avatar
                $model->avatar = $image->name;
            }

            \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Your profile has been updated'));

            $this->trigger(self::EVENT_AFTER_PROFILE_UPDATE, $event);

            return $this->refresh();
        }

        return $this->render('profile', [
            'model' => $model
        ]);
    }

}
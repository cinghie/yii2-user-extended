<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.3.7
 */

namespace cinghie\yii2userextended\controllers;

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
        $model       = $this->finder->findProfileById(Yii::$app->user->identity->getId());
        // Load Old Image
        $oldImage    = $model->avatar;
        // Load avatarPath from Module Params
        $avatarPath  = Yii::getAlias(Yii::$app->getModule('userextended')->avatarPath);

        // Create uploadAvatar Instance
        $image = $model->uploadAvatar($avatarPath);

        // Ajax Validation
        $this->performAjaxValidation($model);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            // revert back if no valid file instance uploaded
            if ($image === false) {
                $model->avatar = $oldImage;
            } else {
                // delete old avatar
                $model->deleteImage($oldImage);
                // upload new avatar
                $model->avatar = $image->name;
            }

            Yii::$app->getSession()->setFlash('success', Yii::t('user', 'Your profile has been updated'));

            return $this->refresh();
        }

        return $this->render('profile', [
            'model' => $model
        ]);
    }

}
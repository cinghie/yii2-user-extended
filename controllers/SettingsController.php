<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.3.1
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
        $model       = $this->finder->findProfileById(Yii::$app->user->identity->getId());
        $oldImage    = $model->avatar;
        $imagePath   = Yii::getAlias('@webroot')."/img/users/";

        // Create uploadAvatar Instance
        $image = $model->uploadAvatar($imagePath);

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

            Yii::$app->getSession()->setFlash('success', Yii::t('user', 'Your profile test has been updated'));

            return $this->refresh();
        }

        return $this->render('profile', [
            'model' => $model,
        ]);
    }

}
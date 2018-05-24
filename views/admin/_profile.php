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

use kartik\widgets\FileInput;
use kartik\widgets\DatePicker;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

?>

<?php $this->beginContent('@dektrium/user/views/admin/update.php', ['user' => $user]) ?>

    <?php $form = ActiveForm::begin([
        'options' => ['enctype'=>'multipart/form-data'],
        'layout' => 'horizontal',
        'enableAjaxValidation' => true,
        'enableClientValidation' => false,
        'fieldConfig' => [
            'horizontalCssClasses' => [
                'wrapper' => 'col-sm-9',
            ],
        ],
    ]); ?>

        <?php if(Yii::$app->getModule('userextended')->avatar): ?>

            <div class="form-group field-profile-avatar-view">
                <label class="control-label col-sm-3" for="profile-avatar-view">Avatar</label>
                <div class="col-sm-9">
                    <input id="profile-avatar-view" class="form-control" name="Profile[avatar-view]" value="<?= $profile->avatar ?>" disabled="" type="text">
                    <div class="help-block help-block-error "></div>
                </div>
            </div>

            <?= $form->field($profile, 'avatar')->widget(FileInput::class, [
                'options' => ['accept'=>'image/*'],
                'pluginOptions' => [
                    'allowedFileExtensions'=> ['jpg','gif','png'],
                    'browseClass' => 'btn btn-primary btn-block',
                    'browseIcon' => '<i class="glyphicon glyphicon-camera"></i> ',
                    'browseLabel' =>  \Yii::t('userextended', 'Change Avatar'),
                    'previewFileType' => 'image',
                    'showCaption' => false,
                    'showRemove' => false,
                    'showUpload' => false,
                ]
            ])->label(false) ?>

        <?php endif ?>

        <?php if(Yii::$app->getModule('userextended')->firstname && Yii::$app->getModule('userextended')->lastname): ?>
            <?= $form->field($profile, 'name') ?>
        <?php endif ?>

        <?php if(Yii::$app->getModule('userextended')->firstname): ?>
            <?= $form->field($profile, 'firstname') ?>
        <?php endif ?>

        <?php if(Yii::$app->getModule('userextended')->lastname): ?>
            <?= $form->field($profile, 'lastname') ?>
        <?php endif ?>

        <?php if(Yii::$app->getModule('userextended')->birthday): ?>
            <?= $form->field($profile, 'birthday')->widget(DatePicker::class, [
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'yyyy-mm-dd',
                ]
            ]); ?>
        <?php endif ?>

        <?php if(Yii::$app->getModule('userextended')->publicEmail): ?>
            <?= $form->field($profile, 'public_email') ?>
        <?php endif ?>

        <?php if(Yii::$app->getModule('userextended')->website): ?>
            <?= $form->field($profile, 'website') ?>
        <?php endif ?>

        <?php if(Yii::$app->getModule('userextended')->location): ?>
            <?= $form->field($profile, 'location') ?>
        <?php endif ?>

        <?php if(Yii::$app->getModule('userextended')->gravatarEmail): ?>
            <?= $form->field($profile, 'gravatar_email') ?>
        <?php endif ?>

        <?php if(Yii::$app->getModule('userextended')->bio): ?>
            <?= $form->field($profile, 'bio')->textarea() ?>
        <?php endif ?>

        <?php if(Yii::$app->getModule('userextended')->signature): ?>
            <?= $form->field($profile, 'signature')->textarea() ?>
        <?php endif ?>

        <div class="form-group">
            <div class="col-lg-offset-3 col-lg-9">
                <?= Html::submitButton(\Yii::t('user', 'Update'), ['class' => 'btn btn-block btn-success']) ?>
            </div>
        </div>

    <?php ActiveForm::end(); ?>

<?php $this->endContent() ?>

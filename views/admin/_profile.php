<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.5.9
 */

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use kartik\widgets\FileInput;

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

<div class="form-group field-profile-avatar-view">
    <label class="control-label col-sm-3" for="profile-avatar-view">Avatar</label>
    <div class="col-sm-9">
        <input id="profile-avatar-view" class="form-control" name="Profile[avatar-view]" value="<?= $profile->avatar ?>" disabled="" type="text">
        <div class="help-block help-block-error "></div>
    </div>
</div>
<?= $form->field($profile, 'avatar')->widget(FileInput::classname(), [
    'options' => ['accept'=>'image/*'],
    'pluginOptions' => [
        'allowedFileExtensions'=> ['jpg','gif','png'],
        'browseClass' => 'btn btn-primary btn-block',
        'browseIcon' => '<i class="glyphicon glyphicon-camera"></i> ',
        'browseLabel' =>  \Yii::t('user', 'Change Avatar'),
        'previewFileType' => 'image',
        'showCaption' => false,
        'showRemove' => false,
        'showUpload' => false,
    ]
])->label(false) ?>
<?= $form->field($profile, 'name') ?>
<?= $form->field($profile, 'firstname') ?>
<?= $form->field($profile, 'lastname') ?>
<?= $form->field($profile, 'birthday') ?>
<?= $form->field($profile, 'public_email') ?>
<?= $form->field($profile, 'website') ?>
<?= $form->field($profile, 'location') ?>
<?= $form->field($profile, 'gravatar_email') ?>
<?= $form->field($profile, 'bio')->textarea() ?>


<div class="form-group">
    <div class="col-lg-offset-3 col-lg-9">
        <?= Html::submitButton(\Yii::t('user', 'Update'), ['class' => 'btn btn-block btn-success']) ?>
    </div>
</div>

<?php ActiveForm::end(); ?>

<?php $this->endContent() ?>

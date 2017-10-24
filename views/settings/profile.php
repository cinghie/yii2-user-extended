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

use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\widgets\FileInput;

$this->title = \Yii::t('user', 'Profile settings');
$this->params['breadcrumbs'][] = $this->title;
?>

<?php if(\Yii::$app->getModule('userextended')->showAlert): ?>
    <?= $this->render('/_alert', [ 'module' => \Yii::$app->getModule('user'), ]) ?>
<?php endif ?>

<div class="row">

    <div class="col-md-3">
        <?= $this->render('_menu') ?>
    </div>

    <div class="col-md-9">

        <div class="panel panel-default">

            <div class="panel-heading">
                <?= Html::encode($this->title) ?>
            </div>

            <div class="panel-body">

                <? $form = ActiveForm::begin([
                    'id' => 'profile-form',
                    'options' => ['class' => 'form-horizontal','enctype'=>'multipart/form-data'],
                    'fieldConfig' => [
                        'template' => "{label}\n<div class=\"col-lg-9\">{input}</div>\n<div class=\"col-sm-offset-3 col-lg-9\">{error}\n{hint}</div>",
                        'labelOptions' => ['class' => 'col-lg-3 control-label'],
                    ],
                    'enableAjaxValidation'   => true,
                    'enableClientValidation' => false,
                    'validateOnBlur'         => false,
                ]); ?>

                <?  if(\Yii::$app->getModule('userextended')->firstname && \Yii::$app->getModule('userextended')->avatar) {
	                echo $form->field( $model, 'avatar' )->widget( FileInput::classname(), [
		                'options'       => [ 'accept' => 'image/*' ],
		                'pluginOptions' => [
			                'allowedFileExtensions' => [ 'jpg', 'gif', 'png' ],
			                'browseClass'           => 'btn btn-primary btn-block',
			                'browseIcon'            => '<i class="glyphicon glyphicon-camera"></i> ',
			                'browseLabel'           => \Yii::t( 'user', 'Change Avatar' ),
			                'previewFileType'       => 'image',
			                'showCaption'           => false,
			                'showRemove'            => false,
			                'showUpload'            => false,
		                ]
	                ]);
                } ?>

                <? if(\Yii::$app->getModule('userextended')->firstname && \Yii::$app->getModule('userextended')->lastname) {
                    echo $form->field($model, 'name')->textInput([
		                'placeholder' => \Yii::t('user', 'Name'),
		                'readonly' => true
	                ]);
                }  ?>

	            <? if(\Yii::$app->getModule('userextended')->firstname) {
		            echo $form->field($model, 'firstname')->textInput([
		                'placeholder' => \Yii::t('user', 'Firstname')
                    ]);
	            } ?>

	            <? if(\Yii::$app->getModule('userextended')->lastname) {
		            echo $form->field($model, 'lastname')->textInput([
                        'placeholder' => \Yii::t('user', 'Lastname')
                    ]);
	            } ?>

                <? if(\Yii::$app->getModule('userextended')->birthday) {
	                echo $form->field($model, 'birthday');
                } ?>

	            <? if(\Yii::$app->getModule('userextended')->publicEmail) {
		            echo $form->field($model, 'public_email')->textInput([
                        'placeholder' => \Yii::t('userextended', 'Public Email')
                    ]);
	            } ?>

                <? if(\Yii::$app->getModule('userextended')->gravatarEmail) {
                    echo $form->field($model, 'gravatar_email')->textInput([
	                    'placeholder' => \Yii::t('userextended', 'Gravatar Email')
                    ])->hint(\yii\helpers\Html::a(\Yii::t('user', 'Change your avatar at Gravatar.com'), 'http://gravatar.com'));
                } ?>

	            <? if(\Yii::$app->getModule('userextended')->website) {
	                echo $form->field($model, 'website')->textInput([
		                'placeholder' => \Yii::t('user', 'Website')
	                ]);
                } ?>

	            <? if(\Yii::$app->getModule('userextended')->location) {
                    echo $form->field($model, 'location')->textInput([
	                    'placeholder' => \Yii::t('user', 'Location')
                    ]);
                } ?>

	            <? if(\Yii::$app->getModule('userextended')->bio) {
	                echo $form->field($model, 'bio')->textarea() ;
                } ?>

                <div class="form-group">
                    <div class="col-lg-12">
                        <?= \yii\helpers\Html::submitButton(\Yii::t('user', 'Save'), ['class' => 'btn btn-block btn-success']) ?><br>
                    </div>
                </div>

                <?php ActiveForm::end(); ?>

            </div>

        </div>

    </div>

</div>

<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.7.0
 */

use dektrium\user\helpers\Timezone;
use kartik\widgets\DatePicker;
use kartik\widgets\FileInput;
use yii\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

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

	            <?php $form = ActiveForm::begin([
                    'id' => 'profile-form',
                    'options' => ['class' => 'form-horizontal','enctype'=>'multipart/form-data'],
                    'fieldConfig' => [
                        'template' => "{label}\n<div class=\"col-lg-9\">{input}</div>\n<div class=\"col-sm-offset-3 col-lg-9\">{error}\n{hint}</div>",
                        'labelOptions' => ['class' => 'col-lg-3 control-label'],
                    ],
                    'enableAjaxValidation' => true,
                    'enableClientValidation' => false,
                    'validateOnBlur' => false,
                ]); ?>

                    <?php if(Yii::$app->getModule('userextended')->avatar): ?>

                        <div class="form-group field-profile-avatar-view">

                            <label class="control-label col-md-3 col-sm-12" for="profile-avatar-view">Avatar</label>

                            <div class="col-md-9 col-sm-12">
                                <input id="profile-avatar-view" class="form-control" name="Profile[avatar-view]" value="<?= $model->avatar ?>" disabled="" type="text">
                                <div class="help-block help-block-error "></div>
                            </div>

                        </div>

                        <div class="form-group field-profile-avatar-change">

                            <div class="col-md-12 col-md-offset-3">

			                    <?= $form->field($model, 'avatar')->widget(FileInput::class, [
			                        'class' => 'col-md-12',
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

                            </div>

                        </div>

                    <?php endif ?>

                    <?php if(\Yii::$app->getModule('userextended')->firstname && \Yii::$app->getModule('userextended')->lastname) {
                        echo $form->field($model, 'name')->textInput([
                            'placeholder' => \Yii::t('userextended', 'Name'),
                            'readonly' => true
                        ]);
                    } ?>

                    <?php if(\Yii::$app->getModule('userextended')->firstname) {
                        echo $form->field($model, 'firstname')->textInput([
                            'placeholder' => \Yii::t('userextended', 'Firstname')
                        ]);
                    } ?>

                    <?php if(\Yii::$app->getModule('userextended')->lastname) {
                        echo $form->field($model, 'lastname')->textInput([
                            'placeholder' => \Yii::t('userextended', 'Lastname')
                        ]);
                    } ?>

                    <?php if(\Yii::$app->getModule('userextended')->birthday) {
                        echo $form->field($model, 'birthday')->widget(DatePicker::class, [
                            'pluginOptions' => [
                                'autoclose' => true,
                                'format' => 'yyyy-mm-dd',
                            ]
                        ]);
                    } ?>

                    <?= $form->field($model, 'timezone')->dropDownList(
                            ArrayHelper::map(
                                Timezone::getAll(),
                                'timezone',
                                'name'
                            )
		            ) ?>

                    <?php if(\Yii::$app->getModule('userextended')->publicEmail) {
                        echo $form->field($model, 'public_email')->textInput([
                            'placeholder' => \Yii::t('userextended', 'Public Email')
                        ]);
                    } ?>

                    <?php if(\Yii::$app->getModule('userextended')->gravatarEmail) {
                        echo $form->field($model, 'gravatar_email')->textInput([
                            'placeholder' => \Yii::t('user', 'Gravatar email')
                        ])->hint(Html::a(\Yii::t('user', 'Change your avatar at Gravatar.com'), 'http://gravatar.com'));
                    } ?>

                    <?php if(\Yii::$app->getModule('userextended')->website) {
                        echo $form->field($model, 'website')->textInput([
                            'placeholder' => \Yii::t('user', 'Website')
                        ]);
                    } ?>

                    <?php if(\Yii::$app->getModule('userextended')->location) {
                        echo $form->field($model, 'location')->textInput([
                            'placeholder' => \Yii::t('user', 'Location')
                        ]);
                    } ?>

                    <?php if(\Yii::$app->getModule('userextended')->bio) {
                        echo $form->field($model, 'bio')->textarea() ;
                    } ?>

                    <?php if(Yii::$app->getModule('userextended')->signature): ?>
                        <?= $form->field($model, 'signature')->textarea() ?>
                    <?php endif ?>

                    <div class="form-group">
                        <div class="col-lg-offset-3 col-lg-9">
                            <?= Html::submitButton(\Yii::t('userextended', 'Save'), ['class' => 'btn btn-block btn-success']) ?><br>
                        </div>
                    </div>

                <?php ActiveForm::end(); ?>

            </div>

        </div>

    </div>

</div>

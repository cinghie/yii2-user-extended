<?php

/** @var $module **/

use dektrium\user\widgets\Connect;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = Yii::t('user', 'Sign in');
$this->params['breadcrumbs'][] = $this->title;

$fieldOptions1 = [
    'options' => ['class' => 'form-group has-feedback'],
    'template' => "{input}<span class='glyphicon glyphicon-envelope form-control-feedback'></span>"
];

$fieldOptions2 = [
    'options' => ['class' => 'form-group has-feedback'],
    'template' => "{input}<span class='glyphicon glyphicon-lock form-control-feedback'></span>"
];

?>

<?= $this->render('/_alert', ['module' => Yii::$app->getModule('user')]) ?>

<div class="login-box">

    <div class="login-logo">
        <a href="#"><b><?= Yii::$app->name ?></b></a>
    </div>

    <div class="login-box-body">

	    <?php if (Yii::$app->session->hasFlash('login')): ?>
            <div class="bg-aqua" style="padding: 10px 0; margin-bottom: 15px;">
                <p class="login-box-msg" style="padding: 0;">
                    <?= Yii::$app->session->getFlash('login') ?>
                </p>
            </div>
	    <?php endif ?>

        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'enableAjaxValidation'   => true,
            'enableClientValidation' => false,
            'validateOnBlur'         => false,
            'validateOnType'         => false,
            'validateOnChange'       => false,
        ]) ?>

        <?= $form->field($model, 'login', $fieldOptions1)
                 ->label(false)
                 ->textInput(['placeholder' => Yii::t('user', 'Email')]) ?>

        <?= $form
            ->field($model, 'password', $fieldOptions2)
            ->label(false)
            ->passwordInput(['placeholder' => $model->getAttributeLabel('password')]) ?>

        <div class="row">
            <div class="col-xs-8">
                <div class="checkbox icheck">
                    <div class="icheckbox_square-blue" style="position: relative;" aria-checked="false" aria-disabled="false">
                        <?= $form->field($model, 'rememberMe')->checkbox() ?>
                    </div>
                </div>
            </div>
            <div class="col-xs-4">
                <?= Html::submitButton(Yii::t('user', 'Sign in'), ['class' => 'btn btn-primary btn-block btn-flat']) ?>
            </div>
        </div>

        <?php ActiveForm::end() ?>

        <?php if (Yii::$app->getModule('userextended')->socialLogin): ?>
            <div class="social-auth-links text-center">
                <p>- OR -</p>
                <a href="#" class="btn btn-block btn-social btn-facebook btn-flat"><i class="fa fa-facebook"></i>
                    Sign in using Facebook</a>
                <a href="#" class="btn btn-block btn-social btn-google btn-flat"><i class="fa fa-google-plus"></i>
                    Sign in using Google+</a>
            </div>
        <?php endif ?>

        <?php if ($module->enableConfirmation): ?>
            <p class="text-center">
                <?= Html::a(Yii::t('user', 'Didn\'t receive confirmation message?'), ['/user/registration/resend']) ?>
            </p>
        <?php endif ?>

        <?php if ($module->enableRegistration): ?>
            <p class="text-center">
                <?= Html::a(Yii::t('user', 'Don\'t have an account? Sign up!'), ['/user/registration/register']) ?>
            </p>
	        <?= Connect::widget([
		        'baseAuthUrl' => ['/user/security/auth'],
	        ]) ?>
        <?php endif ?>
        
    </div>

</div>

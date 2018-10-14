<?php

/** @var $module **/

use dektrium\user\widgets\Connect;
use kartik\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = \Yii::t('user', 'Sign in');
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss('
    h1 {
        margin-bottom: 5px;
    }
    h4 {
        font-size: 15px;
    }
    body {
        color: #555; 
        background-color: #eff1f2!important; 
        font: 400 12px/1.42857 Open Sans,Helvetica,Arial,sans-serif; 
        -webkit-tap-highlight-color: rgba(0,0,0,0); 
    }
    button.btn {
        background-color: #008abd;
        border-radius: 3px;
        box-shadow: none;
        font-size: 12px;
        line-height: 1.33;
        margin-bottom: 10px;
        margin-top: 10px;
        padding: 10px 16px;
        text-transform: uppercase;
    }
    input#login-form-login, input#login-form-password {
        background-color: rgb(250, 255, 189) !important;
        background-image: none !important;
        border: 1px solid #c7d6db;
        border-radius: 3px;
        color: rgb(0, 0, 0) !important;
        cursor: text;
        font-family: Open Sans,Helvetica,Arial,FontAwesome,sans-serif!important;
        font-size: 12px!important;
        line-height: 1.42857!important;
        padding: 10px 8px!important;
        -webkit-appearance: textfield;
        -webkit-rtl-ordering: logical;
    }
    label {
        color: #666;
        font-size: 13px;
        font-weight: 400;
    }
    .form-group {
        margin-bottom: 15px;
    }
    #login-form {
        padding-top: 15px;
    }
    .login-box, .register-box {
        margin: 3% auto;    
        width: 500px;
    }
    .login-box-body {
        margin-top: 115px;
    }
    .login-box-body, .register-box-body {
        box-shadow: 0 1px 3px rgba(0,0,0,.3);
        padding: 40px;
    }
    .login-header {
        color: #6d6d6d;
        margin-bottom: 30px;
    }
    .login-logo {
        z-index: 1;
        position: absolute;
        margin: 0 auto;
        width: 69.5px;
        left: 0;
        right: 0;
    }
    .login-logo img {
        height: 118.5px;
        vertical-align: middle;
    }
    .row-padding-top {
        margin-bottom: 3px;
        padding-top: 2px;
    }
');

?>

<?= $this->render('/_alert', ['module' => \Yii::$app->getModule('user')]) ?>

<div class="login-box">

	<div class="login-header text-center">
		<h1 style="color: #929292; font-size: 24px;"><b><?= \Yii::$app->name ?></b></h1>
        <div><?= \Yii::$app->params['version'] ?></div>
	</div>

    <div class="login-logo">
	    <?= Html::img('@web/logo.png', ['alt'=>'some', 'class'=>'thing']);?>
    </div>

	<div class="login-box-body">

        <h4 class="text-center"><?= \Yii::$app->params['copyright_text'] ?></h4>

		<?php $form = ActiveForm::begin([
			'id' => 'login-form',
			'enableAjaxValidation'   => true,
			'enableClientValidation' => false,
			'validateOnBlur'         => false,
			'validateOnType'         => false,
			'validateOnChange'       => false,
		]) ?>

		<?= $form->field($model, 'login', [
			'addon' => [
				'prepend' => [
					'content'=>'<i class="glyphicon glyphicon-envelope"></i>'
				]
			],
			'options' => ['class' => 'form-group has-feedback']
            ])->label(\Yii::t('user', 'Email'))
            ->textInput(['placeholder' => 'test@example.com']) ?>

		<?= $form->field($model, 'password', [
			'addon' => [
				'prepend' => [
					'content'=>'<i class="glyphicon glyphicon-lock"></i>'
				]
			],
			'options' => ['class' => 'form-group has-feedback']
            ])->label(\Yii::t('user', 'Password'))
			->passwordInput(['placeholder' => $model->getAttributeLabel('password')]) ?>

        <div class="row form-group row-padding-top">
            <div class="col-xs-12">
	            <?= Html::submitButton(\Yii::t('user', 'Sign in'), ['class' => 'btn bg-aqua btn-block']) ?>
            </div>
        </div>

		<div class="row">
			<div class="col-xs-8">
				<div class="checkbox icheck">
					<div class="icheckbox_square-blue" style="position: relative;" aria-checked="false" aria-disabled="false">
						<?= $form->field($model, 'rememberMe')->checkbox() ?>
					</div>
				</div>
			</div>
			<div class="col-xs-4">

			</div>
		</div>

		<?php ActiveForm::end(); ?>

		<?php if (\Yii::$app->getModule('userextended')->socialLogin): ?>
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
				<?= Html::a(\Yii::t('user', 'Didn\'t receive confirmation message?'), ['/user/registration/resend']) ?>
			</p>
		<?php endif ?>

		<?php if ($module->enableRegistration): ?>
			<p class="text-center">
				<?= Html::a(\Yii::t('user', 'Don\'t have an account? Sign up!'), ['/user/registration/register']) ?>
			</p>
		<?php endif ?>

		<?= Connect::widget([
			'baseAuthUrl' => ['/user/security/auth'],
		]) ?>

	</div>

</div>

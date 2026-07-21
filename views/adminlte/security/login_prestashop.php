<?php

/** @var $module **/
/** @var $model \cinghie\userextended\models\LoginForm **/

use dektrium\user\widgets\Connect;
use kartik\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = Yii::t('user', 'Sign in');
$this->params['breadcrumbs'][] = $this->title;

$ue = Yii::$app->getModule('userextended');
$bsVersion = $ue->getBsVersion();
$isBs4 = $ue->isBs4();
$iconEnvelope = $isBs4 ? '<span class="fas fa-envelope"></span>' : '<i class="glyphicon glyphicon-envelope"></i>';
$iconLock = $isBs4 ? '<span class="fas fa-lock"></span>' : '<i class="glyphicon glyphicon-lock"></i>';
$versionText = isset(Yii::$app->params['version']) ? (string) Yii::$app->params['version'] : Yii::t('userextended', 'Set params version');
$copyrightText = isset(Yii::$app->params['copyright_text']) ? (string) Yii::$app->params['copyright_text'] : Yii::t('userextended', 'Set params copyright_text');

if ($isBs4) {
    // AdminLTE 3 layout + light Prestashop branding
    // https://adminlte.io/themes/v3/pages/examples/login.html
    $this->registerCss('
        html, body.login-page {
            height: 100%;
        }
        body.login-page {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        body.login-page > .wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            width: 100%;
            margin: 0;
        }
        .login-page .login-box {
            margin: 0 auto;
        }
        .login-page .login-logo img {
            max-height: 90px;
            margin-bottom: .5rem;
        }
        .login-page .login-logo .login-version {
            display: block;
            font-size: .875rem;
            font-weight: 400;
            color: #6c757d;
            margin-top: .25rem;
        }
        .login-card-body .btn-primary {
            background-color: #008abd;
            border-color: #008abd;
        }
        .login-card-body .btn-primary:hover,
        .login-card-body .btn-primary:focus {
            background-color: #0079a5;
            border-color: #0079a5;
        }
    ');

    $fieldOptions1 = [
        'addon' => [
            'append' => ['content' => $iconEnvelope],
            'groupOptions' => ['class' => 'mb-3'],
        ],
        'options' => ['tag' => false],
        'template' => "{input}\n{hint}\n{error}",
    ];
    $fieldOptions2 = [
        'addon' => [
            'append' => ['content' => $iconLock],
            'groupOptions' => ['class' => 'mb-3'],
        ],
        'options' => ['tag' => false],
        'template' => "{input}\n{hint}\n{error}",
    ];
} else {
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
        .login-box {
            max-width: 100%;
        }
        .login-box, .register-box {
            margin: 3% auto;
        }
        .login-box-body {
            margin-top: 105px;
        }
        .login-box-body, .register-box-body {
            box-shadow: 0 1px 3px rgba(0,0,0,.3);
            padding: 40px;
        }
        .login-header {
            color: #6d6d6d;
            margin-bottom: 25px;
        }
        .login-logo {
            z-index: 1;
            position: absolute;
            margin: 0 auto;
            width: auto;
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
        @media (min-width: 768px) {
            .login-box, .register-box {
                width: 500px;
            }
        }
    ');
}

?>

<?= $this->render('/_alert', ['module' => Yii::$app->getModule('user')]) ?>

<div class="login-box">

    <?php if ($isBs4): ?>

        <div class="login-logo">
            <?= Html::img($ue->templateLogoURL, [
                'alt' => Html::encode(Yii::$app->name),
            ]) ?>
            <a href="#"><b><?= Html::encode(Yii::$app->name) ?></b></a>
            <span class="login-version"><?= Html::encode($versionText) ?></span>
        </div>

        <div class="card">
            <div class="card-body login-card-body">

                <p class="login-box-msg"><?= Html::encode($copyrightText) ?></p>

                <?php if (Yii::$app->session->hasFlash('login')): ?>
                    <p class="login-box-msg text-danger" style="padding-top: 0;">
                        <?= Html::encode(Yii::$app->session->getFlash('login')) ?>
                    </p>
                <?php endif ?>

                <?php $form = ActiveForm::begin([
                    'id' => 'login-form',
                    'bsVersion' => $bsVersion,
                    'enableAjaxValidation'   => !\cinghie\userextended\helpers\TurnstileVerifier::shouldProtectLogin(),
                    'enableClientValidation' => false,
                    'validateOnBlur'         => false,
                    'validateOnType'         => false,
                    'validateOnChange'       => false,
                ]) ?>

                <?= $form->field($model, 'login', $fieldOptions1)
                    ->label(false)
                    ->textInput(['placeholder' => Yii::t('user', 'Email'), 'class' => 'form-control']) ?>

                <?= $form->field($model, 'password', $fieldOptions2)
                    ->label(false)
                    ->passwordInput(['placeholder' => $model->getAttributeLabel('password'), 'class' => 'form-control']) ?>

                <?php if ($model->isCaptchaRequired()): ?>
                    <?= $form->field($model, 'captcha')->widget(\yii\captcha\Captcha::class, [
                        'captchaAction' => Yii::$app->getModule('userextended')->loginCaptchaAction,
                    ])->label(false) ?>
                <?php endif ?>

                <?php if ($model->isTurnstileRequired()): ?>
                    <div class="mb-3">
                        <?= $this->render('@vendor/cinghie/yii2-user-extended/views/_turnstile') ?>
                        <?= $form->field($model, 'turnstileToken')->hiddenInput()->label(false) ?>
                    </div>
                <?php endif ?>

                <div class="row">
                    <div class="col-8">
                        <?= $form->field($model, 'rememberMe', [
                            'options' => ['tag' => false],
                            'template' => '<div class="icheck-primary">{input}{label}</div>',
                        ])->checkbox(['uncheck' => null], false) ?>
                    </div>
                    <div class="col-4">
                        <?= Html::submitButton(Yii::t('user', 'Sign in'), ['class' => 'btn btn-primary btn-block']) ?>
                    </div>
                </div>

                <?php ActiveForm::end() ?>

                <?php if (Yii::$app->getModule('userextended')->socialLogin): ?>
                    <div class="social-auth-links text-center mb-3">
                        <p>- OR -</p>
                        <a href="#" class="btn btn-block btn-primary">
                            <i class="fab fa-facebook mr-2"></i> Sign in using Facebook
                        </a>
                        <a href="#" class="btn btn-block btn-danger">
                            <i class="fab fa-google-plus mr-2"></i> Sign in using Google+
                        </a>
                    </div>
                <?php endif ?>

                <?php if ($module->enableConfirmation): ?>
                    <p class="mb-1">
                        <?= Html::a(Yii::t('user', 'Didn\'t receive confirmation message?'), ['/user/registration/resend']) ?>
                    </p>
                <?php endif ?>

                <?php if ($module->enableRegistration): ?>
                    <p class="mb-0">
                        <?= Html::a(Yii::t('user', 'Don\'t have an account? Sign up!'), ['/user/registration/register'], ['class' => 'text-center']) ?>
                    </p>
                    <?= Connect::widget([
                        'baseAuthUrl' => ['/user/security/auth'],
                    ]) ?>
                <?php endif ?>

            </div>
        </div>

    <?php else: ?>

        <div class="login-header text-center">
            <h1 style="color: #929292; font-size: 24px;"><b><?= Html::encode(Yii::$app->name) ?></b></h1>
            <div><?= Html::encode($versionText) ?></div>
        </div>

        <div class="login-logo">
            <?= Html::img($ue->templateLogoURL, ['alt' => Html::encode(Yii::$app->name), 'class' => 'thing']) ?>
        </div>

        <div class="login-box-body">

            <h4 class="text-center"><?= Html::encode($copyrightText) ?></h4>

            <?php if (Yii::$app->session->hasFlash('login')): ?>
                <div class="bg-aqua" style="padding: 10px 0; margin-bottom: 15px;">
                    <p class="login-box-msg" style="padding: 0;">
                        <?= Html::encode(Yii::$app->session->getFlash('login')) ?>
                    </p>
                </div>
            <?php endif ?>

            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'bsVersion' => $bsVersion,
                'enableAjaxValidation'   => !\cinghie\userextended\helpers\TurnstileVerifier::shouldProtectLogin(),
                'enableClientValidation' => false,
                'validateOnBlur'         => false,
                'validateOnType'         => false,
                'validateOnChange'       => false,
            ]) ?>

            <?= $form->field($model, 'login', [
                'addon' => ['prepend' => ['content' => $iconEnvelope]],
                'options' => ['class' => 'form-group has-feedback'],
            ])->label(Yii::t('user', 'Email'))
                ->textInput(['placeholder' => Yii::t('user', 'Email')]) ?>

            <?= $form->field($model, 'password', [
                'addon' => ['prepend' => ['content' => $iconLock]],
                'options' => ['class' => 'form-group has-feedback'],
            ])->label(Yii::t('user', 'Password'))
                ->passwordInput(['placeholder' => $model->getAttributeLabel('password')]) ?>

            <?php if ($model->isCaptchaRequired()): ?>
                <?= $form->field($model, 'captcha')->widget(\yii\captcha\Captcha::class, [
                    'captchaAction' => Yii::$app->getModule('userextended')->loginCaptchaAction,
                ]) ?>
            <?php endif ?>

            <?php if ($model->isTurnstileRequired()): ?>
                <?= $this->render('@vendor/cinghie/yii2-user-extended/views/_turnstile') ?>
                <?= $form->field($model, 'turnstileToken')->hiddenInput()->label(false) ?>
            <?php endif ?>

            <div class="row form-group row-padding-top">
                <div class="col-xs-12">
                    <?= Html::submitButton(Yii::t('user', 'Sign in'), ['class' => 'btn bg-aqua btn-block']) ?>
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
                <div class="col-xs-4"></div>
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

    <?php endif ?>

</div>

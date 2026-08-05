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

if ($isBs4) {
    // AdminLTE 3: https://adminlte.io/themes/v3/pages/examples/login.html
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
    $fieldOptions1 = [
        'options' => ['class' => 'form-group has-feedback'],
        'template' => "{input}<span class='glyphicon glyphicon-envelope form-control-feedback'></span>",
    ];
    $fieldOptions2 = [
        'options' => ['class' => 'form-group has-feedback'],
        'template' => "{input}<span class='glyphicon glyphicon-lock form-control-feedback'></span>",
    ];
}

?>

<?= $this->render('/_alert', ['module' => Yii::$app->getModule('user')]) ?>

<div class="login-box">

    <div class="login-logo">
        <a href="#"><b><?= Html::encode(Yii::$app->name) ?></b></a>
    </div>

    <?php if ($isBs4): ?>
    <div class="card">
        <div class="card-body login-card-body">

            <p class="login-box-msg"><?= Yii::t('user', 'Sign in') ?></p>

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
                    'options' => ['class' => 'form-control', 'placeholder' => Yii::t('userextended', 'Captcha')],
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
    <div class="login-box-body">

        <?php if (Yii::$app->session->hasFlash('login')): ?>
            <div class="bg-warning" style="padding: 10px 0; margin-bottom: 15px;">
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

        <?= $form->field($model, 'login', $fieldOptions1)
            ->label(false)
            ->textInput(['placeholder' => Yii::t('user', 'Email')]) ?>

        <?= $form->field($model, 'password', $fieldOptions2)
            ->label(false)
            ->passwordInput(['placeholder' => $model->getAttributeLabel('password')]) ?>

        <?php if ($model->isCaptchaRequired()): ?>
            <?= $form->field($model, 'captcha')->widget(\yii\captcha\Captcha::class, [
                'captchaAction' => Yii::$app->getModule('userextended')->loginCaptchaAction,
                'options' => ['class' => 'form-control', 'placeholder' => Yii::t('userextended', 'Captcha')],
            ])->label(false) ?>
        <?php endif ?>

        <?php if ($model->isTurnstileRequired()): ?>
            <?= $this->render('@vendor/cinghie/yii2-user-extended/views/_turnstile') ?>
            <?= $form->field($model, 'turnstileToken')->hiddenInput()->label(false) ?>
        <?php endif ?>

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
    <?php endif ?>

</div>

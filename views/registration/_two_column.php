<?php

/**
 * @var yii\web\View $this
 * @var dektrium\user\models\User $model
 * @var dektrium\user\Module $module
 */

use dektrium\user\widgets\Connect;
use kartik\widgets\ActiveForm;
use kartik\widgets\DatePicker;
use yii\captcha\Captcha;
use yii\helpers\Html;

?>

    <div class="col-md-8 col-md-offset-2">

        <div class="panel panel-default">

            <div class="panel-heading">
                <h3 class="panel-title"><?= Html::encode($this->title) ?></h3>
            </div>

            <div class="panel-body">

	            <?php $form = ActiveForm::begin([
		            'id' => 'registration-form',
		            'enableAjaxValidation' => false,
		            'enableClientValidation' => true,
	            ]) ?>

                <div class="col-md-12 form-register">

                    <div class="row form-register-fields">

                        <div class="col-md-6">

				            <?php if(Yii::$app->getModule('userextended')->firstname): ?>

					            <?= $form->field($model, 'firstname') ?>

				            <?php endif ?>

				            <?= $form->field($model, 'email') ?>

				            <?= $form->field($model, 'username') ?>

                        </div>

                        <div class="col-md-6">

				            <?php if(Yii::$app->getModule('userextended')->lastname): ?>

					            <?= $form->field($model, 'lastname') ?>

				            <?php endif ?>

				            <?php if(Yii::$app->getModule('userextended')->birthday): ?>

					            <?= $form->field($model, 'birthday')->widget(DatePicker::class, [
						            'pluginOptions' => [
							            'autoclose' => true,
							            'format' => 'yyyy-mm-dd',
						            ]
					            ]) ?>

				            <?php endif ?>

				            <?php if ($module->enableGeneratingPassword === false): ?>
					            <?= $form->field($model, 'password')->passwordInput() ?>
				            <?php endif ?>

                        </div>

                    </div>

		            <?php if(Yii::$app->getModule('userextended')->captcha): ?>

                        <div class="row form-register-captcha">

                            <div class="col-md-12">

					            <?= $form->field($model, 'captcha')->widget(Captcha::class, [
						            'captchaAction' => ['/site/captcha'],
						            'options' => ['class' => 'form-control'],
						            'template' => '<div class="row"><div class="col-md-6">{input}</div><div class="col-md-6">{image}</div></div>'
					            ]) ?>

                            </div>

                        </div>

		            <?php endif ?>

		            <?php if(Yii::$app->getModule('userextended')->terms): ?>

                        <div class="row form-register-terms">

                            <div class="col-md-4">

		                        <?= $form->field($model, 'terms')->checkbox(['uncheck' => false, 'checked' => true]) ?>

                            </div>

                            <div class="col-md-8">

		                        <?= Yii::t('userextended', 'By clicking I Agree, you agree to the Terms and Conditions set out by this site, including our Cookie Use.') ?>

                            </div>

                        </div>

		            <?php endif ?>

                    <div class="row form-register-buttons">

                        <div class="col-md-12">

				            <?= Html::submitButton(Yii::t('userextended', 'Register'), ['class' => 'btn btn-success btn-block btn-lg']) ?>

                        </div>

                    </div>

                    <div class="row social-login-button">

                        <div class="col-md-12">

			                <?= Connect::widget([
				                'baseAuthUrl' => ['/user/security/auth'],
			                ]) ?>

                        </div>

                    </div>

                </div>

	            <?php ActiveForm::end() ?>

            </div>

        </div>

    </div>

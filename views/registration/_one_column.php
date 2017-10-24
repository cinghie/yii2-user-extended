<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.5.2
 */

use kartik\widgets\ActiveForm;
use kartik\widgets\DatePicker;
use yii\captcha\Captcha;
use yii\helpers\Html;

?>

    <div class="col-md-6 col-md-offset-3">

        <div class="panel panel-default panel-register">

            <div class="panel-heading">
                <h3 class="panel-title"><?= Html::encode($this->title) ?></h3>
            </div>

            <div class="panel-body">

	            <?php $form = ActiveForm::begin([
		            'id' => 'registration-form',
		            'enableAjaxValidation' => false,
		            'enableClientValidation' => true,
	            ]); ?>

                <div class="col-md-12 form-register">

                    <div class="row form-register-fields">

                        <div class="col-md-12">

				            <?= $form->field($model, 'username') ?>

				            <?= $form->field($model, 'email') ?>

				            <?php if ($module->enableGeneratingPassword == false): ?>
					            <?= $form->field($model, 'password')->passwordInput() ?>
				            <?php endif ?>

				            <?php if(Yii::$app->getModule('userextended')->firstname): ?>

					            <?= $form->field($model, 'firstname') ?>

				            <?php endif ?>

				            <?php if(Yii::$app->getModule('userextended')->lastname): ?>

					            <?= $form->field($model, 'lastname') ?>

				            <?php endif ?>

				            <?php if(Yii::$app->getModule('userextended')->birthday): ?>

					            <?= $form->field($model, 'birthday')->widget(DatePicker::className(), [
						            'pluginOptions' => [
							            'autoclose' => true,
							            'format' => 'yyyy-mm-dd',
						            ]
					            ]); ?>

				            <?php endif ?>

                        </div>

                    </div>

		            <?php if(Yii::$app->getModule('userextended')->captcha): ?>

                        <div class="row form-register-captcha">

                            <div class="col-md-12">

					            <?= $form->field($model, 'captcha')->widget(Captcha::className(), [
						            'captchaAction' => ['/site/captcha'],
						            'options' => ['class' => 'form-control'],
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

		                        <?= \Yii::t('userextended', 'By clicking I Agree, you agree to the Terms and Conditions set out by this site, including our Cookie Use.') ?>

                            </div>

                        </div>

		            <?php endif ?>

                    <div class="row form-register-buttons">

                        <div class="col-md-12">

				            <?= Html::submitButton(\Yii::t('userextended', 'Register'), ['class' => 'btn btn-success btn-block btn-lg']) ?>

                        </div>

                    </div>

                </div>

	            <?php ActiveForm::end(); ?>

            </div>

        </div>

    </div>

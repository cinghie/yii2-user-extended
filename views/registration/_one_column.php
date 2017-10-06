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

use yii\widgets\ActiveForm;
use yii\captcha\Captcha;
use yii\helpers\Html;

?>

<?php $form = ActiveForm::begin([
	'id' => 'registration-form',
	'enableAjaxValidation' => false,
	'enableClientValidation' => true,
]); ?>

	<div class="col-md-12">

		<div class="row">

			<div class="col-md-6">

				<?= $form->field($model, 'username') ?>

				<?= $form->field($model, 'email') ?>

				<?php if ($module->enableGeneratingPassword == false): ?>
					<?= $form->field($model, 'password')->passwordInput() ?>
				<?php endif ?>

				<? if(Yii::$app->getModule('userextended')->firstname): ?>

					<?= $form->field($model, 'firstname') ?>

				<? endif ?>

				<? if(Yii::$app->getModule('userextended')->lastname): ?>

					<?= $form->field($model, 'lastname') ?>

				<? endif ?>

				<? if(Yii::$app->getModule('userextended')->birthday): ?>

					<?= $form->field($model, 'birthday') ?>

				<? endif ?>

			</div>

		</div>

		<? if(Yii::$app->getModule('userextended')->captcha): ?>

			<div class="row">

				<div class="col-md-12">

					<?= $form->field($model, 'captcha')->widget(Captcha::className(), [
						'captchaAction' => ['/site/captcha'],
						'options' => ['class' => 'form-control'],
						//'template' => '<div class="row"><div class="col-md-6">{input}</div><div class="col-md-6">{image}</div></div>'
					]) ?>

				</div>

			</div>

		<? endif ?>

		<? if(Yii::$app->getModule('userextended')->terms): ?>

			<div class="row">

				<div class="col-md-12">

					<div class="col-md-3">

						<?= $form->field($model, 'terms')->checkbox(['uncheck' => false, 'checked' => true]) ?>

					</div>

					<div class="col-md-9">

						<?= \Yii::t('userextended', 'By clicking I Agree, you agree to the Terms and Conditions set out by this site, including our Cookie Use.') ?>

					</div>

				</div>

			</div>

		<? endif ?>

		<div class="row">

			<div class="col-md-6">

				<?= Html::submitButton(\Yii::t('user', 'Register'), ['class' => 'btn btn-success btn-block btn-lg']) ?>

			</div>

			<div class="col-md-6">

				<?= Html::a(\Yii::t('user', 'Login'), ['/user/login'], ['class' => 'btn btn-primary btn-block btn-lg']) ?>

			</div>

		</div>

	</div>

<?php ActiveForm::end(); ?>
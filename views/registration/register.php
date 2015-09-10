<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.2.0
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = Yii::t('user', 'Sign up');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><?= Html::encode($this->title) ?></h3>
            </div>
            <div class="panel-body">
                <?php $form = ActiveForm::begin([
                    'id'                     => 'registration-form',
                    'enableAjaxValidation'   => true,
                    'enableClientValidation' => false,
                ]); ?>

                <div class="col-md-6">

                    <?= $form->field($model, 'firstname') ?>

                    <?= $form->field($model, 'birthday') ?>

                    <?= $form->field($model, 'username') ?>

                </div>

                <div class="col-md-6">

                    <?= $form->field($model, 'lastname') ?>

                    <?= $form->field($model, 'email') ?>

                    <?php if ($module->enableGeneratingPassword == false): ?>
                        <?= $form->field($model, 'password')->passwordInput() ?>
                    <?php endif ?>

                </div>

                <div class="col-md-12">

                    <div class="col-md-3">

                        <?= $form->field($model, 'terms')->checkbox(['uncheck' => false, 'checked' => true]) ?>

                    </div>

                    <div class="col-md-9">

                        <?= Yii::t('user', 'By clicking Register, you agree to the Terms and Conditions set out by this site, including our Cookie Use.') ?>

                    </div>

                </div>

                <div class="col-md-12">

                    <?= $form->field($model, 'terms', ['template' => "{error}"])->error() ?>

                </div>

                <div class="col-md-6">

                    <?= Html::submitButton(Yii::t('user', 'Register'), ['class' => 'btn btn-success btn-block btn-lg']) ?>

                </div>

                <div class="col-md-6">

                    <?= Html::a(Yii::t('user', 'Login'), ['/user/login'], ['class' => 'btn btn-primary btn-block btn-lg']) ?>

                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
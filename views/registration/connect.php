<?php

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var cinghie\userextended\models\User $model
 * @var cinghie\userextended\models\Account $account
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = Yii::t('user', 'Sign in');
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="row">

    <div class="col-sm-6 col-sm-offset-3">

        <div class="panel panel-default">

            <div class="panel-heading">
                <h3 class="panel-title"><?= Html::encode($this->title) ?></h3>
            </div>

            <div class="panel-body">

                <div class="alert alert-info">
                    <p>
                        <?= Yii::t('userextended', 'In order to finish your registration, we need you to enter following fields') ?>
                    </p>
                </div>

                <?php $form = ActiveForm::begin([
                    'id' => 'connect-account-form',
                ]); ?>

                <?= $form->field($model, 'email') ?>

                <?= $form->field($model, 'username') ?>

                <?= Html::submitButton(Yii::t('user', 'Continue'), ['class' => 'btn btn-success btn-block']) ?>

                <?php ActiveForm::end(); ?>

            </div>

        </div>

        <p class="text-center">
            <?= Html::a(
                Yii::t(
                    'user',
                    'If you already registered, sign in and connect this account on settings page'
                ),
                ['/user/settings/networks']
            ) ?>.
        </p>

    </div>

</div>

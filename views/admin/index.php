<?php

/**
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var cinghie\userextended\models\UserSearch $searchModel
 * @var yii\web\View $this
 */

use cinghie\userextended\models\User;
use kartik\grid\CheckboxColumn;
use kartik\grid\GridView;
use kartik\select2\Select2;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\DatePicker;

$this->title = Yii::t('user', 'Manage users');
$this->params['breadcrumbs'][] = $this->title;

// Register action buttons js
$this->registerJs('$(document).ready(function() 
    {'
    .$searchModel->getUpdateButtonJavascript('#w2')
    .$searchModel->getDeleteButtonJavascript('#w2')
    .$searchModel->getActiveButtonJavascript('#w2')
    .$searchModel->getDeactiveButtonJavascript('#w2').
    '});
');

?>

<?php if(Yii::$app->getModule('userextended')->showTitles): ?>
    <h1><?= Yii::t('user', 'Manage users') ?></h1>
<?php endif ?>

<?php if(Yii::$app->getModule('userextended')->showAlert): ?>
    <?= $this->render('/_alert', [ 'module' => Yii::$app->getModule('user'), ]) ?>
<?php endif ?>

<div class="row">

    <!-- action menu -->
    <div class="col-md-6">

		<?= $this->render('/admin/_menu') ?>

    </div>

    <!-- action buttons -->
    <div class="col-md-6">

		<?= $searchModel->getDeactiveButton() ?>

		<?= $searchModel->getActiveButton() ?>

		<?= $searchModel->getResetButton() ?>

		<?= $searchModel->getDeleteButton() ?>

		<?= $searchModel->getUpdateButton() ?>

		<?= $searchModel->getCreateButton() ?>

    </div>

</div>

<div class="separator"></div>

<?= GridView::widget([
	'dataProvider' => $dataProvider,
	'filterModel' => $searchModel,
	'layout' => "{items}\n{pager}",
	'containerOptions' => ['class' => 'users-pjax-container'],
	'pjax' => true,
	'pjaxSettings' => [
		'neverTimeout' => true,
	],
	'columns' => [
		[
			'class' => CheckboxColumn::class
		],
		[
			'attribute' => 'username',
			'format' => 'html',
			'hAlign' => 'center',
			'value' => function ($model) {
				/** @var User $model */
				$url = urldecode(Url::toRoute(['/user/admin/update', 'id' => $model->id]));
				return Html::a($model->username,$url);
			}
		],
		[
			'attribute' => 'firstname',
			'hAlign' => 'center',
			'value' => 'profile.firstname',
			'visible' => Yii::$app->getModule('userextended')->firstname ? true : false,
		],
		[
			'attribute' => 'lastname',
			'hAlign' => 'center',
			'value' => 'profile.lastname',
			'visible' => Yii::$app->getModule('userextended')->lastname ? true : false,
		],
		[
			'attribute' => 'birthday',
			'hAlign' => 'center',
			'value' => 'profile.birthday',
			'visible' => Yii::$app->getModule('userextended')->birthday ? true : false,
			'width' => '9%',
		],
		[
			'attribute' => 'email',
			'format' => 'email',
			'hAlign' => 'center',
		],
		[
			'attribute' => 'created_at',
			'filter' => DatePicker::widget([
				'model' => $searchModel,
				'attribute' => 'created_at',
				'dateFormat' => 'php:Y-m-d',
				'options' => [
					'class' => 'form-control',
				],
			]),
			'hAlign' => 'center',
			'value' => function ($model) {
				/** @var User $model */
				return date('Y-m-d H:i:s', $model->created_at);
			},
		],
		[
			'attribute' => 'last_login_at',
			'hAlign' => 'center',
			'filter' => DatePicker::widget([
				'model' => $searchModel,
				'attribute'  => 'last_login_at',
				'dateFormat' => 'php:Y-m-d',
				'options' => [
					'class' => 'form-control',
				],
			]),
			'value' => function ($model)
            {
				/** @var User $model */
				if (!$model->last_login_at || $model->last_login_at === 0) {
					return Yii::t('userextended', 'Never');
				}

				if (extension_loaded('intl')) {
					return Yii::t('userextended', '{0, date, YYYY-MM-dd HH:mm}', [$model->last_login_at]);
				}

				return date('Y-m-d G:i:s', $model->last_login_at);
			},
		],
		[
			'attribute' => 'rule',
            'filterType' => GridView::FILTER_SELECT2,
            'filter' => $searchModel->getNameList(),
            'filterWidgetOptions' => [
                'pluginOptions' => ['allowClear' => true],
            ],
            'filterInputOptions' => ['placeholder' => ''],
			'format' => 'html',
			'hAlign' => 'center',
            'label' => Yii::t( 'userextended', 'Role' ),
			'width' => '8%',
			'value' => function ($model) {
				$html = '';
				/** @var User $model */
				foreach($model->getRolesHTML() as $role){
					$html .= $role['item_name'] . '<br>';
				}
				return $html;
			},
		],
		[
		    'attribute' => 'blocked_at',
			'label' => Yii::t('userextended', 'Enabled'),
		    'filterType' => GridView::FILTER_SELECT2,
		    'filter' => [
			    1 => Yii::t('traits', 'Actived'),
			    0 => Yii::t('traits', 'Inactived')
		    ],
		    'filterWidgetOptions' => [
			    'pluginOptions' => ['allowClear' => true],
		    ],
		    'filterInputOptions' => ['placeholder' => ''],
		    'format' => 'raw',
			'hAlign' => 'center',
			'width' => '6%',
			'value' => function ($model)
            {
				/** @var User $model */
				if ($model->isBlocked) {
					return Html::a('<span class="glyphicon glyphicon-remove text-danger"></span>', ['block', 'id' => $model->id], [
						'data-method' => 'post',
						'data-confirm' => Yii::t('user', 'Are you sure you want to unblock this user?'),
					]);
				}

				return Html::a('<span class="glyphicon glyphicon-ok text-success">', ['block', 'id' => $model->id], [
					'data-method' => 'post',
					'data-confirm' => Yii::t('user', 'Are you sure you want to block this user?'),
				]);
			},
		],
		[
			'header' => Yii::t('userextended', 'Actived'),
			'format' => 'raw',
			'hAlign' => 'center',
			'visible' => Yii::$app->getModule('user')->enableConfirmation,
			'width' => '5%',
			'value' => function ($model) {
				/** @var User $model */
				if ($model->isConfirmed) {
					return '<span class="glyphicon glyphicon-ok text-success"></span>';
				}

				return Html::a('<span class="glyphicon glyphicon-remove text-danger"></span>', ['confirm', 'id' => $model->id], [
					'data-method' => 'post',
					'data-confirm' => Yii::t('user', 'Are you sure you want to confirm this user?'),
				]);
			},
		],
		[
			'attribute' => 'id',
			'hAlign' => 'center',
			'width' => '5%',
		],
	],
	'responsive' => true,
	'responsiveWrap' => true,
	'hover' => true,
	'panel' => [
		'heading'    => '<h3 class="panel-title"><i class="fa fa-user-plus"></i></h3>',
		'type'       => 'success',
		'showFooter' => false
	],
]) ?>

<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.4.0
 */

use cinghie\yii2userextended\models\UserSearch;
use yii\data\ActiveDataProvider;
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\DatePicker;
use yii\web\View;
use yii\widgets\Pjax;

$this->title = Yii::t('user', 'Manage users');
$this->params['breadcrumbs'][] = $this->title;

?>

<?php if(Yii::$app->getModule('userextended')->showTitles): ?>
    <h1><?= Yii::t('user', 'Manage users') ?></h1>
<?php endif ?>

<?= $this->render('/_alert', [ 'module' => Yii::$app->getModule('user'), ]) ?>

<?= $this->render('/admin/_menu') ?>

<?php Pjax::begin() ?>

<?= GridView::widget([
    'dataProvider' 	=> $dataProvider,
    'filterModel'  	=> $searchModel,
    'layout'  		=> "{items}\n{pager}",
    'columns' => [
        [
            'class' => '\kartik\grid\CheckboxColumn'
        ],
        [
            'attribute' => 'username',
            'format' => 'html',
            'hAlign' => 'center',
            'value' => function ($model) {
                $url = urldecode(Url::toRoute(['admin/update', 'id' => $model->id]));
                return Html::a($model->username,$url);
            }
        ],
        [
            'attribute' => 'firstname',
            'hAlign' => 'center',
            'value' => 'profile.firstname',
        ],
        [
            'attribute' => 'lastname',
            'hAlign' => 'center',
            'value' => 'profile.lastname',
        ],
        [
            'attribute' => 'birthday',
            'hAlign' => 'center',
            'value' => 'profile.birthday',
        ],
        [
            'attribute' => 'email',
            'format' => 'email',
            'hAlign' => 'center',
        ],
        [
            'attribute' => 'created_at',
            'filter' => DatePicker::widget([
                'model'      => $searchModel,
                'attribute'  => 'created_at',
                'dateFormat' => 'php:Y-m-d',
                'options' => [
                    'class' => 'form-control',
                ],
            ]),
            'hAlign' => 'center',
            'value' => function ($model) {
                    return date('Y-m-d H:i:s', $model->created_at);
            },
        ],
        [
            'header' => Yii::t('userextended', 'Enabled'),
            'format' => 'raw',
            'hAlign' => 'center',
            'value' => function ($model) {
                if ($model->isBlocked) {
                    return Html::a('<span class="glyphicon glyphicon-remove text-danger"></span>', ['block', 'id' => $model->id], [
                        'data-method' => 'post',
                        'data-confirm' => Yii::t('user', 'Are you sure you want to unblock this user?'),
                    ]);
                } else {
                    return Html::a('<span class="glyphicon glyphicon-ok text-success">', ['block', 'id' => $model->id], [
                        'data-method' => 'post',
                        'data-confirm' => Yii::t('user', 'Are you sure you want to block this user?'),
                    ]);
                }
            },
        ],
        [
            'header' => Yii::t('userextended', 'Actived'),
            'format' => 'raw',
            'hAlign' => 'center',
            'visible' => Yii::$app->getModule('user')->enableConfirmation,
            'value' => function ($model) {
                if ($model->isConfirmed) {
                    return '<span class="glyphicon glyphicon-ok text-success"></span>';
                } else {
                    return Html::a('<span class="glyphicon glyphicon-remove text-danger"></span>', ['confirm', 'id' => $model->id], [
                        'data-method' => 'post',
                        'data-confirm' => Yii::t('user', 'Are you sure you want to confirm this user?'),
                    ]);
                }
            },
        ],
        [
            'attribute' => 'id',
        ],
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{delete}',
        ],
    ],
    'responsive' => true,
    'hover' => true,
    'panel' => [
        'before' => '<span style="margin-right: 5px;">'.
            Html::a('<i class="glyphicon glyphicon-plus"></i> '.Yii::t('userextended', 'New'),
                ['create'], ['class' => 'btn btn-success']
            ).'</span><span style="margin-right: 5px;">'.
            Html::a('<i class="glyphicon glyphicon-pencil"></i> '.Yii::t('userextended', 'Modify'),
                ['update'], ['class' => 'btn btn-warning']
            ).'</span><span style="margin-right: 5px;">'.
            Html::a('<i class="glyphicon glyphicon-minus-sign"></i> '.Yii::t('userextended', 'Delete'),
                ['delete'], ['class' => 'btn btn-danger']
            ).'</span>',
        'heading'    => '<h3 class="panel-title"><i class="fa fa-user-plus"></i></h3>',
        'type'       => 'success',
        'showFooter' => false
    ],
]); ?>

<?php Pjax::end() ?>

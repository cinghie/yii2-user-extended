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

use kartik\grid\GridView;
use yii\grid\ActionColumn;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('userextended', 'Manage permissions');
$this->params['breadcrumbs'][] = $this->title;

?>

<?php if(Yii::$app->getModule('userextended')->showTitles): ?>
    <h1><?= Yii::t('rbac', 'Permissions') ?></h1>
<?php endif ?>

<?php $this->beginContent('@dektrium/rbac/views/layout.php') ?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel'  => $filterModel,
    'layout'       => "{items}\n{pager}",
    'columns'      => [
        [
            'class' => '\kartik\grid\CheckboxColumn'
        ],
        [
            'attribute' => 'name',
            'hAlign' => 'center',
            'header'    => Yii::t('rbac', 'Name'),
        ],
        [
            'attribute' => 'description',
            'hAlign' => 'center',
            'header'    => Yii::t('rbac', 'Description'),
        ],
        [
            'attribute' => 'rule_name',
            'hAlign' => 'center',
            'header'    => Yii::t('rbac', 'Rule name'),
        ],
        [
            'class'      => ActionColumn::className(),
            'template'   => '{update} {delete}',
            'urlCreator' => function ($action, $model) {
                return Url::to(['/rbac/permission/' . $action, 'name' => $model['name']]);
            },
        ]
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
        'heading'    => '<h3 class="panel-title"><i class="fa fa-user-secret"></i></h3>',
        'type'       => 'success',
        'showFooter' => false
    ],
]) ?>

<?php $this->endContent() ?>

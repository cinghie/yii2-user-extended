<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.5.4
 */

use kartik\grid\GridView;
use kartik\grid\ActionColumn;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('userextended', 'Manage roles');
$this->params['breadcrumbs'][] = $this->title;

?>

<?php if(Yii::$app->getModule('userextended')->showTitles): ?>
    <h1><?= Yii::t('userextended', 'Manage roles') ?></h1>
<?php endif ?>

<?php $this->beginContent('@dektrium/rbac/views/layout.php') ?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel'  => $filterModel,
    'layout'       => "{items}\n{pager}",
    'columns'      => [
        [
            'attribute' => 'name',
            'format' => 'html',
            'hAlign' => 'center',
            'header'    => Yii::t('rbac', 'Name'),
            'value' => function ($model) {
                $url = Url::to(['/rbac/role/update', 'name' => $model['name']]);
                return Html::a($model['name'],$url);
            }
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
                return Url::to(['/rbac/role/' . $action, 'name' => $model['name']]);
            },
        ]
    ],
    'responsive' => true,
    'hover' => true,
    'panel' => [
        'before' => '<span style="margin-right: 5px;">'.
            Html::a('<i class="glyphicon glyphicon-plus"></i> '.Yii::t('userextended', 'New'),
                ['create'], ['class' => 'btn btn-new btn-success']
            ).'</span>',
        'heading'    => '<h3 class="panel-title"><i class="fa fa-users"></i></h3>',
        'type'       => 'success',
        'showFooter' => false
    ],
]); ?>

<?php $this->endContent() ?>

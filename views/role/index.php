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
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;

$this->title = Yii::t('userextended', 'Manage roles');
$this->params['breadcrumbs'][] = $this->title;

// Register action buttons js
$this->registerJs('
    $(document).ready(function()
    {
        $("a.btn-update").click(function() {
            var selectedId = $("#w1").yiiGridView("getSelectedRows");

            if(selectedId.length == 0) {
                alert("'.Yii::t("userextended", "Select at least one item").'");
            }
            else if(selectedId.length>1){
                alert("'.Yii::t("userextended", "Select only 1 item").'");
            } else {
                var url = "'.Url::to(['/rbac/role/update']).'&id="+selectedId[0];
                window.location.href= url;
            }
        });
    });
');

?>

<?php if(Yii::$app->getModule('userextended')->showTitles): ?>
    <h1><?= Yii::t('userextended', 'Manage roles') ?></h1>
<?php endif ?>

<?php $this->beginContent('@dektrium/rbac/views/layout.php') ?>

<?php Pjax::begin() ?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel'  => $filterModel,
    'layout'       => "{items}\n{pager}",
    'containerOptions' => ['class' => 'roles-pjax-container'],
    'columns'      => [
        [
            'class' => '\kartik\grid\CheckboxColumn'
        ],
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
        ]
    ],
    'responsive' => true,
    'hover' => true,
    'panel' => [
        'before' => '<span style="margin-right: 5px;">'.
            Html::a('<i class="glyphicon glyphicon-plus"></i> '.Yii::t('userextended', 'New'),
                ['create'], ['class' => 'btn btn-new btn-success']
            ).'</span><span style="margin-right: 5px;">'.
            Html::a('<i class="glyphicon glyphicon-pencil"></i> '.Yii::t('userextended', 'Update'),
                '#', ['class' => 'btn btn-update btn-warning']
            ).'</span><span style="margin-right: 5px;">'.
            Html::a('<i class="glyphicon glyphicon-minus-sign"></i> '.Yii::t('userextended', 'Delete'),
                '#', ['class' => 'btn  btn-delete btn-danger']
            ).'</span>',
        'heading'    => '<h3 class="panel-title"><i class="fa fa-users"></i></h3>',
        'type'       => 'success',
        'showFooter' => false
    ],
]); ?>

<?php Pjax::end() ?>

<?php $this->endContent() ?>

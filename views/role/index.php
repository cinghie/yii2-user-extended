<?php

/**
 * @var $dataProvider array
 * @var $filterModel dektrium\rbac\models\Search
 * @var $this yii\web\View
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

<div class="row">

    <!-- action menu -->
    <div class="col-md-6">

        <?= $this->renderFile('@vendor/dektrium/yii2-user/views/admin/_menu.php') ?>

    </div>

    <!-- action buttons -->
    <div class="col-md-6">



    </div>

</div>

<div class="separator"></div>

<div class="row">

    <div class="col-md-12">

	    <?= GridView::widget([
		    'dataProvider' => $dataProvider,
		    'filterModel' => $filterModel,
		    'layout' => "{items}\n{pager}",
		    'pjax' => true,
		    'pjaxSettings' => [
			    'neverTimeout' => true,
		    ],
		    'columns' => [
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
				    'class'      => ActionColumn::class,
				    'template'   => '{update} {delete}',
				    'urlCreator' => function ($action, $model) {
					    return Url::to(['/rbac/role/' . $action, 'name' => $model['name']]);
				    },
			    ]
		    ],
		    'responsive' => true,
		    'responsiveWrap' => true,
		    'hover' => true,
		    'panel' => [
			    'heading'    => '<h3 class="panel-title"><i class="fa fa-users"></i></h3>',
			    'type'       => 'success',
			    'showFooter' => false
		    ],
	    ]) ?>

    </div>

</div>

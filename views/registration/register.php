<?php

/**
 * @var yii\web\View $this
 * @var dektrium\user\models\User $model
 * @var dektrium\user\Module $module
 */

$this->title = Yii::t('user', 'Sign up');
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="row">

	<?= $this->render(Yii::$app->getModule('userextended')->templateRegister, [
		'model' => $model,
		'module' => $module
	]) ?>

</div>

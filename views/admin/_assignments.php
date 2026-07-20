<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.4
 */

use Yii;
use cinghie\userextended\widgets\Assignments;
use yii\bootstrap\Alert;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var dektrium\user\models\User $user
 * @var cinghie\userextended\models\Assignment|null $model
 */
?>

<?php $this->beginContent('@dektrium/user/views/admin/update.php', ['user' => $user]) ?>

<?= Alert::widget([
	'options' => [
		'class' => 'alert-info alert-dismissible',
	],
	'body' => Yii::t('user', 'You can assign multiple roles or permissions to user by using the form below'),
]) ?>

<?php if ($model !== null && $model->hasErrors()): ?>
	<?= Html::errorSummary($model, ['class' => 'alert alert-danger']) ?>
<?php endif ?>

<?= Assignments::widget([
	'userId' => $user->id,
	'model' => $model ?? null,
	'processPost' => false,
]) ?>

<?php $this->endContent() ?>

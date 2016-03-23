<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.5.8
 */

use yii\widgets\Menu;

$user    = Yii::$app->user->identity;
$profile = $user->profile;
$avatar  = $profile->getImageUrl();
$networksVisible = count(Yii::$app->authClientCollection->clients) > 0;

?>

<div class="panel panel-default">

    <div class="panel-heading">
        <h3 class="panel-title" style="text-align: center;">
            <?= $user->username ?>
        </h3>
    </div>

    <div class="user-image">
        <a href="#" style="display: block; max-width: 100%; padding: 15px 15px 0;">
            <img src="<?php echo $avatar ?>" alt="<?php echo $user->username ?>" title="<?php echo $user->username ?>" style="border: 1px solid #ddd; margin-left: auto; margin-right: auto; max-width: 100%; padding: 15px;">
        </a>
    </div>

    <div class="panel-body">
        <?= Menu::widget([
            'options' => [
                'class' => 'nav nav-pills nav-stacked',
            ],
            'items' => [
                ['label' => Yii::t('user', 'Profile'), 'url' => ['/user/settings/profile']],
                ['label' => Yii::t('user', 'Account'), 'url' => ['/user/settings/account']],
                ['label' => Yii::t('user', 'Networks'), 'url' => ['/user/settings/networks'], 'visible' => $networksVisible],
            ],
        ]) ?>
    </div>

</div>

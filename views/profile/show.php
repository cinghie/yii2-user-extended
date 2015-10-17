<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.3.5
 */

use yii\helpers\Html;

$this->title = empty($profile->name) ? Html::encode($profile->user->username) : Html::encode($profile->name);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-md-12 user-image">
        <div class="row">
            <div class="col-md-3">
                <?php if($profile->gravatar_id): ?>
                    <a href="#" class="thumbnail" title="<?php echo $profile->name ?>">
                        <img class="img-rounded img-responsive" src="http://gravatar.com/avatar/<?= $profile->gravatar_id ?>?s=230" alt="<?php echo $profile->name ?>" title="<?php echo $profile->name ?>" style="padding: 15px;" />
                    </a>
                <?php else: ?>
                    <a href="#" class="thumbnail" title="<?php echo $profile->name ?>">
                        <img class="img-rounded img-responsive" src="http://www.bdu.edu.et/emti/sites/bdu.edu.et.emti/files/default_images/no-profile-img.gif" alt="<?php echo $profile->name ?>" title="<?php echo $profile->name ?>" style="padding: 15px;" />
                    </a>
                <?php endif ?>
                <h1 style="font-size: 20px; padding: 0; text-align: center;"><?= $this->title ?></h1>
                <ul style="padding: 0; list-style: none outside none;">
                    <li style="text-align: center;">
                        <i class="glyphicon glyphicon-time text-muted"></i>
                        <?= Yii::t('user', 'Joined on {0, date}', $profile->user->created_at) ?>
                    </li>
                </ul>
            </div>
            <div class="col-md-9">
                <ul style="padding: 0; list-style: none outside none;">
                    <?php if (!empty($profile->firstname)): ?>
                        <li><?= Html::encode($profile->firstname)." ".Html::encode($profile->lastname) ?></li>
                    <?php endif; ?>
                    <?php if (!empty($profile->birthday)): ?>
                        <li>
                            <?= Html::encode($profile->birthday) ?>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($profile->location)): ?>
                        <li>
                            <i class="glyphicon glyphicon-map-marker text-muted"></i>
                            <?= Html::encode($profile->location) ?>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($profile->website)): ?>
                        <li>
                            <i class="glyphicon glyphicon-globe text-muted"></i>
                            <?= Html::a(Html::encode($profile->website), Html::encode($profile->website), ['title' => $profile->name." Website" ]) ?>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($profile->public_email)): ?>
                        <li>
                            <i class="glyphicon glyphicon-envelope text-muted"></i>
                            <?= Html::a(Html::encode($profile->public_email), 'mailto:' . Html::encode($profile->public_email), ['title' => $profile->name." Email" ]) ?>
                        </li>
                    <?php endif; ?>
                </ul>
                <?php if (!empty($profile->bio)): ?>
                    <p><?= Html::encode($profile->bio) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php

/**
 * @var \yii\web\View $this
 * @var \cinghie\userextended\models\Profile $profile
 */

use cinghie\userextended\helpers\SafeHtml;
use yii\helpers\Html;

$title = empty($profile->name) ? $profile->user->username : $profile->name;
$this->title = $title;
$this->params['breadcrumbs'][] = $this->title;

$safeName = SafeHtml::encode($title);
$imageUrl = SafeHtml::encode($profile->getImageUrl());
$websiteUrl = SafeHtml::safeHttpUrl($profile->website);

?>

<div class="row">
    <div class="col-md-12 user-image">
        <div class="row">
            <div class="col-md-3">
                <?php if ($profile->gravatar_id): ?>
                    <a href="#" class="thumbnail" title="<?= $safeName ?>">
                        <img class="img-rounded img-responsive" src="https://gravatar.com/avatar/<?= SafeHtml::encode($profile->gravatar_id) ?>?s=230" alt="<?= $safeName ?>" title="<?= $safeName ?>" style="padding: 15px;" />
                    </a>
                <?php else: ?>
                    <a href="#" class="thumbnail" title="<?= $safeName ?>">
                        <img class="img-rounded img-responsive" src="<?= $imageUrl ?>" alt="<?= $safeName ?>" title="<?= $safeName ?>" style="padding: 15px;" />
                    </a>
                <?php endif ?>
                <h1 style="font-size: 20px; padding: 0; text-align: center;"><?= $safeName ?></h1>
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
                        <li><?= SafeHtml::encode($profile->firstname) ?> <?= SafeHtml::encode($profile->lastname) ?></li>
                    <?php endif ?>
                    <?php if (!empty($profile->birthday)): ?>
                        <li>
                            <?= SafeHtml::encode($profile->birthday) ?>
                        </li>
                    <?php endif ?>
                    <?php if (!empty($profile->location)): ?>
                        <li>
                            <i class="glyphicon glyphicon-map-marker text-muted"></i>
                            <?= SafeHtml::encode($profile->location) ?>
                        </li>
                    <?php endif ?>
                    <?php if ($websiteUrl !== null): ?>
                        <li>
                            <i class="glyphicon glyphicon-globe text-muted"></i>
                            <?= Html::a(
                                SafeHtml::encode($profile->website),
                                $websiteUrl,
                                [
                                    'title' => $safeName . ' Website',
                                    'rel' => 'noopener noreferrer',
                                    'target' => '_blank',
                                ]
                            ) ?>
                        </li>
                    <?php endif ?>
                    <?php if (!empty($profile->public_email)): ?>
                        <li>
                            <i class="glyphicon glyphicon-envelope text-muted"></i>
                            <?= Html::mailto(
                                $profile->public_email,
                                $profile->public_email,
                                ['title' => $safeName . ' Email']
                            ) ?>
                        </li>
                    <?php endif ?>
                </ul>
                <?php if (!empty($profile->bio)): ?>
                    <p><?= $profile->getBioHtml() ?></p>
                <?php endif ?>
                <?php if (!empty($profile->signature)): ?>
                    <div class="user-signature"><?= $profile->getSignatureHtml() ?></div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

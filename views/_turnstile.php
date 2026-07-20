<?php

/**
 * @var yii\web\View $this
 */

use cinghie\userextended\assets\TurnstileAsset;
use cinghie\userextended\helpers\TurnstileVerifier;
use yii\helpers\Html;

if (!TurnstileVerifier::isConfigured()) {
	return;
}

TurnstileAsset::register($this);

echo Html::tag('div', '', [
	'class' => 'cf-turnstile',
	'data-sitekey' => TurnstileVerifier::getSiteKey(),
	'data-theme' => TurnstileVerifier::getTheme(),
	'style' => 'margin-bottom: 15px;',
]);

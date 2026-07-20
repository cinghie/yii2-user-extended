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

namespace cinghie\userextended\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Cloudflare Turnstile client script (loaded only when widget is enabled).
 */
class TurnstileAsset extends AssetBundle
{
	/**
	 * @var string[]
	 */
	public $js = [
		'https://challenges.cloudflare.com/turnstile/v0/api.js',
	];

	/**
	 * @var array
	 */
	public $jsOptions = [
		'async' => true,
		'defer' => true,
		'position' => View::POS_END,
	];
}

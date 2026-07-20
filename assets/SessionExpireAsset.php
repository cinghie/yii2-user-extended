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
use yii\web\YiiAsset;

/**
 * Client-side session expire redirect + non-blocking warning toast.
 *
 * sourcePath points at assets/static (js+css only), so PHP asset classes are never
 * published — including when AssetManager::$linkAssets is enabled.
 *
 * Production loads *.min.*; URLs use appendTimestamp (?v=mtime) for cache busting.
 */
class SessionExpireAsset extends AssetBundle
{
	/**
	 * {@inheritdoc}
	 */
	public $depends = [
		YiiAsset::class,
	];

	/**
	 * {@inheritdoc}
	 */
	public function init()
	{
		$staticDir = __DIR__ . DIRECTORY_SEPARATOR . 'static';
		$resolved = realpath($staticDir);
		$this->sourcePath = $resolved !== false ? $resolved : $staticDir;

		$useMin = $this->shouldUseMinifiedAssets();

		$jsFile = $useMin ? 'js/session-expire.min.js' : 'js/session-expire.js';
		$cssFile = $useMin ? 'css/session-expire.min.css' : 'css/session-expire.css';

		$this->js = [
			[$jsFile, 'appendTimestamp' => true],
		];
		$this->css = [
			[$cssFile, 'appendTimestamp' => true],
		];

		// Defaults first; caller/DI publishOptions win on conflict
		$defaults = [];
		// Republish every request in debug so local edits are visible without flushing assets
		if (defined('YII_DEBUG') && YII_DEBUG) {
			$defaults['forceCopy'] = true;
		}
		$this->publishOptions = array_merge($defaults, $this->publishOptions);

		parent::init();
	}

	/**
	 * Prefer minified assets in production / when debug is off.
	 */
	protected function shouldUseMinifiedAssets(): bool
	{
		if (defined('YII_ENV_PROD') && YII_ENV_PROD) {
			return true;
		}

		if (defined('YII_DEBUG') && !YII_DEBUG) {
			return true;
		}

		return false;
	}
}

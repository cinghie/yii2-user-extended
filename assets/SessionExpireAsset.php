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
 * Client-side session expire redirect asset.
 */
class SessionExpireAsset extends AssetBundle
{
    /**
     * {@inheritdoc}
     */
    public $sourcePath = '@vendor/cinghie/yii2-user-extended/assets';

    /**
     * {@inheritdoc}
     */
    public $js = [
        'js/session-expire.js',
    ];

    /**
     * {@inheritdoc}
     */
    public $depends = [
        YiiAsset::class,
    ];
}

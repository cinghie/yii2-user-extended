<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.3.4
 */

namespace cinghie\yii2userextended;

use Yii;
use dektrium\user\Module as BaseUser;

class Module extends BaseUser
{
    public $avatarPath = "";

    /**
     * @var string Module version
     */
    protected $_version = "0.3.4";

    /**
     * @var boolean Module version
     */
    public $captcha = true;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->modules = [
            'user' => [
                'class' => 'dektrium\user\Module',
                'identityClass' => 'dektrium\user\models\User',
            ],
        ];

        // Translate
        $this->registerTranslations();
    }

    /**
     * Get module version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * Translating module message
     */
    public function registerTranslations()
    {
        if (empty(Yii::$app->i18n->translations['userextended']))
        {
            Yii::$app->i18n->translations['userextended'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'basePath' => __DIR__ . '/messages',
                //'forceTranslation' => true,
            ];
        }
    }

}
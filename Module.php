<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.1
 */

namespace cinghie\userextended;

use Yii;
use dektrium\user\Module as BaseUser;

class Module extends BaseUser
{
    /**
     * @var string Module version
     */
    protected $_version = "0.6.0";

    /**
     * @var string Path to avatar file
     */
    public $avatarPath = '@webroot/img/users/';

    /**
     * @var string URL to avatar file
     */
    public $avatarURL  = '@web/img/users/';

	/**
	 * @var string default User Role
	 */
	public $defaultRole = '';

	/**
	 * @var boolean birthday
	 */
	public $birthday = true;

	/**
	 * @var boolean firstname
	 */
	public $firstname = true;

	/**
	 * @var boolean lastname
	 */
	public $lastname = true;

	/**
	 * @var boolean Captcha
	 */
	public $captcha = true;

	/**
	 * @var boolean onlyEmail
	 */
	public $onlyEmail = false;

	/**
	 * @var boolean onlyEmail
	 */
	public $terms = true;

	/**
	 * @var boolean showAlert in views
	 */
	public $templateRegister = '_two_column';

    /**
     * @var boolean showAlert in views
     */
    public $showAlert = true;

    /**
     * @var boolean showTitles in views
     */
    public $showTitles = true;

    /**
     * @var boolean showTitles in views
     */
    public $socialLogin = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

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
        if (empty(\Yii::$app->i18n->translations['userextended']))
        {
            \Yii::$app->i18n->translations['userextended'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'basePath' => __DIR__ . '/messages',
                //'forceTranslation' => true,
            ];
        }
    }

}

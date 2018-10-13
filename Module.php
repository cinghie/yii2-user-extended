<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.7.0
 */

namespace cinghie\userextended;

use dektrium\user\Module as BaseUser;
use yii\i18n\PhpMessageSource;

class Module extends BaseUser
{
    /**
     * @var string Module version
     */
    private $version = '0.7.0';

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
	 * @var boolean avatar
	 */
	public $avatar = true;

	/**
	 * @var boolean bio
	 */
	public $bio = false;

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
	 * @var boolean captcha
	 */
	public $captcha = true;

	/**
	 * @var boolean gravatar
	 */
	public $gravatarEmail = false;

	/**
	 * @var boolean location
	 */
	public $location = false;

	/**
	 * @var boolean onlyEmail
	 */
	public $onlyEmail = false;

	/**
	 * @var boolean publicEmail
	 */
	public $publicEmail = false;

	/**
	 * @var boolean signature
	 */
	public $signature = true;

	/**
	 * @var boolean terms
	 */
	public $terms = true;

	/**
	 * @var boolean website
	 */
	public $website = false;

	/**
	 * @var string login template
	 */
	public $templateLogin = 'login';

	/**
	 * @var string register template
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
     * @var boolean socialLogin
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
        return $this->version;
    }

    /**
     * Translating module message
     */
    public function registerTranslations()
    {
        if (empty(\Yii::$app->i18n->translations['userextended']))
        {
            \Yii::$app->i18n->translations['userextended'] = [
                'class' => PhpMessageSource::class,
                'basePath' => __DIR__ . '/messages',
                //'forceTranslation' => true,
            ];
        }
    }

}

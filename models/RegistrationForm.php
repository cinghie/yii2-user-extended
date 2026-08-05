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

namespace cinghie\userextended\models;

use Exception;
use Yii;
use cinghie\userextended\helpers\ModuleConfig;
use cinghie\userextended\helpers\PasswordPolicy;
use cinghie\userextended\helpers\SecurityAudit;
use cinghie\userextended\helpers\TurnstileVerifier;
use dektrium\user\models\RegistrationForm as BaseRegistrationForm;
use yii\base\InvalidConfigException;
use yii\base\InvalidArgumentException;

/**
 * Registration form with optional captcha, Turnstile, terms, and password policy.
 *
 * When enabling public registration, also set user.enableConfirmation and map
 * RegistrationController for IP/email throttle; remove BackendFilter if present.
 */
class RegistrationForm extends BaseRegistrationForm
{
    /**
     * @var string $name
     * @var string $firstname
     * @var string $lastname
     * @var integer $terms
     * @var string $captcha
     * @var string|null $turnstileToken
     */
    public $name;
    public $firstname;
    public $lastname;
    public $birthday;
    public $terms;
    public $captcha;
    public $turnstileToken;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();

	    unset($rules['passwordLength']);
	    $rules['passwordPolicy'] = PasswordPolicy::rule('password');

	    if (ModuleConfig::get('birthday')) {
		    $rules[] = ['birthday', 'safe'];
		    $rules[] = ['birthday', 'date', 'format' => 'yyyy-mm-dd'];
		    $rules[] = ['birthday', 'required'];
	    }

	    if (ModuleConfig::get('captcha')) {
		    $rules[] = ['captcha', 'captcha'];
		    $rules[] = ['captcha', 'required'];
	    }

	    if (TurnstileVerifier::isEnabledForRegistration()) {
		    $rules[] = [
			    'turnstileToken',
			    'required',
			    'when' => static function () {
				    // ActiveForm ajax validation only — forged XHR registration must still require Turnstile
				    return !TurnstileVerifier::isActiveFormAjaxValidationRequest();
			    },
			    'message' => Yii::t('userextended', 'Security verification failed. Please try again.'),
		    ];
		    $rules[] = [
			    'turnstileToken',
			    function ($attribute) {
				    // Tokens are single-use: never call siteverify during ActiveForm AJAX validation
				    if (TurnstileVerifier::isActiveFormAjaxValidationRequest() || $this->hasErrors($attribute)) {
					    return;
				    }
				    if (!TurnstileVerifier::verify($this->$attribute)) {
					    $this->addError(
						    $attribute,
						    Yii::t('userextended', 'Security verification failed. Please try again.')
					    );
					    SecurityAudit::log('turnstile_fail', 0, [
						    'context' => 'registration',
						    'email' => SecurityAudit::safeLogin($this->email),
					    ], 'auth', 'User', '/user/registration/register');
				    }
			    },
		    ];
	    }

	    if (ModuleConfig::get('firstname')) {
		    $rules[] = ['firstname', 'trim'];
		    $rules[] = ['firstname', 'string', 'max' => 255];
		    $rules[] = ['firstname', 'required'];
	    }

	    if (ModuleConfig::get('lastname')) {
		    $rules[] = ['lastname', 'trim'];
		    $rules[] = ['lastname', 'string', 'max' => 255];
		    $rules[] = ['lastname', 'required'];
	    }

	    if (ModuleConfig::get('terms')) {
		    $rules[] = ['terms', 'required', 'requiredValue' => true, 'message' => Yii::t('userextended','You must agree to the terms and conditions')];
	    }

        return $rules;
    }

	/**
	 * @inheritdoc
	 */
	public function beforeValidate()
	{
		if (TurnstileVerifier::isEnabledForRegistration()) {
			$posted = Yii::$app->request->post('cf-turnstile-response');
			if (is_string($posted) && $posted !== '') {
				$this->turnstileToken = $posted;
			}
		}

		return parent::beforeValidate();
	}

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        $labels = parent::attributeLabels();
        $labels['name']      = Yii::t('userextended', 'Name');
        $labels['firstname'] = Yii::t('userextended', 'Firstname');
        $labels['lastname']  = Yii::t('userextended', 'Lastname');
        $labels['birthday']  = Yii::t('userextended', 'Birthday');
        $labels['terms']     = Yii::t('userextended', 'I Agree');
        $labels['captcha']   = Yii::t('userextended', 'Captcha');

        return $labels;
    }

	/**
	 * Registers a new user account. If registration was successful it will set flash message.
	 *
	 * @return bool
	 * @throws Exception
	 * @throws InvalidConfigException
	 * @throws InvalidArgumentException
	 */
	public function register()
	{
		if (!$this->validate()) {
			return false;
		}

		/** @var User $user */
		$user = Yii::createObject(User::class);
		$user->setScenario('register');
		$this->loadAttributes($user);

		if (!$user->register()) {
			return false;
		}

		if(Yii::$app->getModule('userextended')->defaultRole !== '') {
			$user->setRole(Yii::$app->getModule('userextended')->defaultRole);
		}

		Yii::$app->session->setFlash(
			'info',
			Yii::t(
				'user',
				'Your account has been created and a message with further instructions has been sent to your email'
			)
		);

		return true;
	}

	/**
	 * @inheritdoc
	 *
	 * @throws InvalidConfigException
	 */
    protected function loadAttributes(\dektrium\user\models\User $user)
    {
        $user->setAttributes([
            'email'    => $this->email,
            'username' => $this->username,
            'password' => $this->password,
        ]);

	    $profile = Yii::createObject(Profile::class);

	    if(Yii::$app->getModule('userextended')->birthday) {
		    $profile->setAttributes([
			    'birthday' => $this->birthday
		    ]);
	    }

	    if(Yii::$app->getModule('userextended')->firstname) {
		    $profile->setAttributes([
			    'firstname' => ucwords(strtolower($this->firstname))
		    ]);
	    }

	    if(Yii::$app->getModule('userextended')->lastname) {
		    $profile->setAttributes([
			    'lastname' => ucwords(strtolower($this->lastname))
		    ]);
	    }

	    if(Yii::$app->getModule('userextended')->firstname && Yii::$app->getModule('userextended')->lastname) {
		    $profile->setAttributes([
			    'name' => ucwords(strtolower($this->firstname)).' '.ucwords(strtolower($this->lastname))
		    ]);
	    }

	    if(Yii::$app->getModule('userextended')->terms) {
		    $profile->setAttributes([
			    'terms' => $this->terms
		    ]);
	    }

        $user->setProfile($profile);
    }
}

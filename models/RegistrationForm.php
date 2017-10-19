<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.0
 */

namespace cinghie\userextended\models;

use Yii;
use dektrium\user\models\RegistrationForm as BaseRegistrationForm;

class RegistrationForm extends BaseRegistrationForm
{

    /**
     * Add a new fields
     *
     * string $name
     * string $firstname
     * string $lastname
     * integer $terms
     * string $captcha
     */
    public $name;
    public $firstname;
    public $lastname;
    public $birthday;
    public $terms;
    public $captcha;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();

	    if(\Yii::$app->getModule('userextended')->birthday) {
		    $rules[] = ['birthday', 'safe'];
		    $rules[] = ['birthday', 'date', 'format' => 'yyyy-mm-dd'];
		    $rules[] = ['birthday', 'required'];
	    }

	    if(\Yii::$app->getModule('userextended')->captcha) {
		    $rules[] = ['captcha', 'captcha'];
		    $rules[] = ['captcha', 'required'];
	    }

	    if(\Yii::$app->getModule('userextended')->firstname) {
		    $rules[] = ['firstname', 'trim'];
		    $rules[] = ['firstname', 'string', 'max' => 255];
		    $rules[] = ['firstname', 'required'];
	    }

	    if(\Yii::$app->getModule('userextended')->lastname) {
		    $rules[] = ['lastname', 'trim'];
		    $rules[] = ['lastname', 'string', 'max' => 255];
		    $rules[] = ['lastname', 'required'];
	    }

	    if(\Yii::$app->getModule('userextended')->terms) {
		    $rules[] = ['terms', 'required', 'requiredValue' => true, 'message' => \Yii::t('userextended','You must agree to the terms and conditions')];
	    }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        $labels = parent::attributeLabels();
        $labels['name']      = \Yii::t('userextended', 'Name');
        $labels['firstname'] = \Yii::t('userextended', 'Firstname');
        $labels['lastname']  = \Yii::t('userextended', 'Lastname');
        $labels['birthday']  = \Yii::t('userextended', 'Birthday');
        $labels['terms']     = \Yii::t('userextended', 'I Agree');
        $labels['captcha']   = \Yii::t('userextended', 'Captcha');

        return $labels;
    }

	/**
	 * Registers a new user account. If registration was successful it will set flash message.
	 *
	 * @return bool
	 */
	public function register()
	{
		if (!$this->validate()) {
			return false;
		}

		/** @var User $user */
		$user = Yii::createObject(User::className());
		$user->setScenario('register');
		$this->loadAttributes($user);

		if (!$user->register()) {
			return false;
		}

		if(\Yii::$app->getModule('userextended')->defaultRole !== '') {
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
	 * @throws \yii\base\InvalidConfigException
	 */
    protected function loadAttributes(\dektrium\user\models\User $user)
    {
        $user->setAttributes([
            'email'    => $this->email,
            'username' => $this->username,
            'password' => $this->password,
        ]);

	    $profile = \Yii::createObject(Profile::className());

	    if(\Yii::$app->getModule('userextended')->birthday) {
		    $profile->setAttributes([
			    'birthday' => $this->birthday
		    ]);
	    }

	    if(\Yii::$app->getModule('userextended')->firstname) {
		    $profile->setAttributes([
			    'firstname' => ucwords(strtolower($this->firstname))
		    ]);
	    }

	    if(\Yii::$app->getModule('userextended')->lastname) {
		    $profile->setAttributes([
			    'lastname' => ucwords(strtolower($this->lastname))
		    ]);
	    }

	    if(\Yii::$app->getModule('userextended')->firstname && \Yii::$app->getModule('userextended')->lastname) {
		    $profile->setAttributes([
			    'name' => ucwords(strtolower($this->firstname))." ".ucwords(strtolower($this->lastname))
		    ]);
	    }

	    if(\Yii::$app->getModule('userextended')->terms) {
		    $profile->setAttributes([
			    'terms' => $this->terms
		    ]);
	    }

        $user->setProfile($profile);
    }

}
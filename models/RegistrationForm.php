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

use dektrium\rbac\models\Assignment;
use dektrium\user\models\RegistrationForm as BaseRegistrationForm;

class RegistrationForm extends BaseRegistrationForm
{

    /**
     * Add a new fields
     * string name
     * string firstname
     * string lastname
     * integer terms
     * string captcha
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
		    $rules[] = ['birthday', 'required'];
		    $rules[] = ['birthday', 'safe'];
		    $rules[] = ['birthday', 'date', 'format' => 'yyyy-mm-dd'];
	    }

	    if(\Yii::$app->getModule('userextended')->captcha) {
		    $rules[] = ['captcha', 'required'];
		    $rules[] = ['captcha', 'captcha'];
	    }

	    if(\Yii::$app->getModule('userextended')->firstname) {
		    $rules[] = ['firstname', 'required'];
		    $rules[] = ['firstname', 'string', 'max' => 255];
	    }

	    if(\Yii::$app->getModule('userextended')->lastname) {
		    $rules[] = ['lastname', 'required'];
		    $rules[] = ['lastname', 'string', 'max' => 255];
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
        $labels['name']      = \Yii::t('user', 'Name');
        $labels['firstname'] = \Yii::t('user', 'Firstname');
        $labels['lastname']  = \Yii::t('user', 'Lastname');
        $labels['birthday']  = \Yii::t('user', 'Birthday');
        $labels['terms']     = \Yii::t('user', 'I Agree');
        $labels['captcha']   = \Yii::t('user', 'Captcha');

        return $labels;
    }

    /**
     * @inheritdoc
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
			    'birthday'  => $this->birthday
		    ]);
	    }

	    if(\Yii::$app->getModule('userextended')->firstname) {
		    $profile->setAttributes([
			    'firstname' => ucwords(strtolower($this->firstname))
		    ]);
	    }

	    if(\Yii::$app->getModule('userextended')->lastname) {
		    $profile->setAttributes([
			    'lastname'  => ucwords(strtolower($this->lastname))
		    ]);
	    }

	    if(\Yii::$app->getModule('userextended')->firstname && \Yii::$app->getModule('userextended')->lastname) {
		    $profile->setAttributes([
			    'name'      => ucwords(strtolower($this->firstname))." ".ucwords(strtolower($this->lastname))
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
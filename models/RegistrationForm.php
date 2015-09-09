<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.1.5
 */

namespace cinghie\yii2userextended\models;

use dektrium\user\models\RegistrationForm as BaseRegistrationForm;

class RegistrationForm extends BaseRegistrationForm
{
    /**
     * Add a new fields
     * @var string name
     * @var string firstname
     * @var string lastname
     */
    public $name;
    public $firstname;
    public $lastname;
    public $birthday;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['name','firstname','lastname','birthday'], 'required'];
        $rules[] = [['name','firstname','lastname'], 'string', 'max' => 255];
        $rules[] = [['birthday'], 'safe'];
        $rules[] = ['birthday', 'date', 'format' => 'yyyy-mm-dd'];
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
        return $labels;
    }

    /**
     * @inheritdoc
     */
    public function loadAttributes(User $user)
    {
        // here is the magic happens
        $user->setAttributes([
            'email'    => $this->email,
            'username' => $this->username,
            'password' => $this->password,
        ]);
        /** @var Profile $profile */
        $profile = \Yii::createObject(Profile::className());
        $profile->setAttributes([
            'name'      => $this->name,
            'firstname' => $this->firstname,
            'lastname'  => $this->lastname,
            'birthday'  => $this->birthday,
        ]);
        $user->setProfile($profile);
    }

}
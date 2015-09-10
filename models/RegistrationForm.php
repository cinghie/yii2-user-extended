<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.1.7
 */

namespace cinghie\yii2userextended\models;

use dektrium\user\models\RegistrationForm as BaseRegistrationForm;

class RegistrationForm extends BaseRegistrationForm
{
    /**
     * Add a new fields
     * string name
     * string firstname
     * string lastname
     * integer terms
     */
    public $name;
    public $firstname;
    public $lastname;
    public $birthday;
    public $terms;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['firstname','lastname','birthday','terms'], 'required'];
        $rules[] = [['firstname','lastname'], 'string', 'max' => 255];
        $rules[] = [['birthday'], 'safe'];
        $rules[] = ['birthday', 'date', 'format' => 'yyyy-mm-dd'];
        $rules[] = ['terms', 'required', 'requiredValue' => true, 'message' => 'You must agree to the terms and conditions'];
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
        return $labels;
    }

    /**
     * @inheritdoc
     */
    public function loadAttributes(User $user)
    {
        $user->setAttributes([
            'email'    => $this->email,
            'username' => $this->username,
            'password' => $this->password,
        ]);

        $profile = \Yii::createObject(Profile::className());
        $profile->setAttributes([
            'name'      => $this->firstname." ".$this->lastname,
            'firstname' => $this->firstname,
            'lastname'  => $this->lastname,
            'birthday'  => $this->birthday,
            'terms'     => $this->terms,
        ]);
        $user->setProfile($profile);
    }

}
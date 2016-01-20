<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.5.4
 */

namespace cinghie\yii2userextended\models;

use cinghie\yii2userextended\models\Assignments;
use dektrium\user\models\User as BaseUser;
use yii\db\Query;
use Yii;

class User extends BaseUser
{
    /** @inheritdoc */
    public function attributeLabels()
    {
        return [
            'username'          => Yii::t('user', 'Username'),
            'firstname'         => Yii::t('userextended', 'Firstname'),
            'lastname'          => Yii::t('userextended', 'Lastname'),
            'birthday'          => Yii::t('userextended', 'Birthday'),
            'roles'             => Yii::t('userextended', 'Roles'),
            'email'             => Yii::t('user', 'Email'),
            'registration_ip'   => Yii::t('user', 'Registration ip'),
            'unconfirmed_email' => Yii::t('user', 'New email'),
            'password'          => Yii::t('user', 'Password'),
            'created_at'        => Yii::t('user', 'Registration time'),
            'confirmed_at'      => Yii::t('user', 'Confirmation time'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProfile()
    {
        return $this->hasOne($this->module->modelMap['Profile'], ['user_id' => 'id'])->from(Profile::tableName() . ' AS profile');
    }

    /**
     * @return user roles
     */
    public function getRoles()
    {
        return $this->hasMany(Assignment::className(), ['user_id' => 'id'])->from(Assignment::tableName() . ' AS role');
    }

    /**
     * @return html roles for roles column in admin index
     */
    public function getRolesHTML()
    {
        $query = new Query;
        $query->select('item_name')
              ->from('{{%auth_assignment}}')
              ->where('user_id='.$this->id);
        $command = $query->createCommand();
        $roles   = $command->queryAll();

        return $roles;
    }

}

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

namespace cinghie\userextended\models;

use Yii;
use dektrium\user\models\User as BaseUser;
use yii\db\Query;

/**
 *
 * @property mixed $role
 * @property array[] $rolesHTML
 * @property \yii\db\ActiveQuery $roles
 */
class User extends BaseUser
{

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'username'          => \Yii::t('user', 'Username'),
            'firstname'         => \Yii::t('userextended', 'Firstname'),
            'lastname'          => \Yii::t('userextended', 'Lastname'),
            'birthday'          => \Yii::t('userextended', 'Birthday'),
            'roles'             => \Yii::t('userextended', 'Roles'),
            'email'             => \Yii::t('user', 'Email'),
            'registration_ip'   => \Yii::t('user', 'Registration ip'),
            'unconfirmed_email' => \Yii::t('user', 'New email'),
            'password'          => \Yii::t('user', 'Password'),
            'created_at'        => \Yii::t('user', 'Registration time'),
            'confirmed_at'      => \Yii::t('user', 'Confirmation time'),
            'last_login_at'     => \Yii::t('userextended', 'Last login'),
        ];
    }

	/**
	 * If onlyEmail is true, username is email
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function beforeValidate()
	{
		if(Yii::$app->getModule('userextended')->onlyEmail) {
			$this->username = $this->email;
		}

		return parent::beforeValidate();
	}

	/**
     * @return \yii\db\ActiveQuery
     */
    public function getProfile()
    {
        return $this->hasOne($this->module->modelMap['Profile'], ['user_id' => 'id'])->from(Profile::tableName() . ' AS profile');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRoles()
    {
        return $this->hasMany(Assignment::class, ['user_id' => 'id'])->from(Assignment::tableName() . ' AS role');
    }

	/**
	 * Get Rules by UserID
	 *
	 * @param $userid
	 * @return \yii\rbac\Role[]
	 */
    public function getRulesByUserID($userid)
    {
        return \Yii::$app->authManager->getRolesByUser($userid);
    }

	/**
	 * Set Role to User
	 *
	 * @param $role
	 *
	 * @throws \Exception
	 * @throws \yii\base\InvalidParamException
	 */
    public function setRole($role)
    {
	    $auth = Yii::$app->authManager;
	    $roleObject = $auth->getRole($role);

	    if (!$roleObject) {
		    throw new yii\base\InvalidParamException ("There is no role \"$role\".");
	    }

	    $auth->assign($roleObject, $this->id);
    }

	/**
	 * Array roles for roles column in admin index
	 *
	 * @return array []
	 * @throws \yii\db\Exception
	 */
    public function getRolesHTML()
    {
        $query = new Query;
        $query->select('item_name')
              ->from('{{%auth_assignment}}')
              ->where('user_id='.$this->id);
        $command = $query->createCommand();

        return $command->queryAll();
    }

}

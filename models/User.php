<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.3
 */

namespace cinghie\userextended\models;

use Exception;
use Yii;
use dektrium\user\models\User as BaseUser;
use yii\base\InvalidArgumentException;
use yii\db\ActiveQuery;
use yii\db\Query;
use yii\helpers\Html;
use yii\rbac\Role;

/**
 * User ActiveRecord extends \dektrium\user\models\User
 *
 * @property ActiveQuery $roles
 * @property mixed $role
 * @property array[] $rolesHTML
 * @property string $fullName
 * @property string $avatar
 * @property Role[] $currentUserRoles
 * @property Assignment[] $currentUserRolesAssigned
 */
class User extends BaseUser
{
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'username' => Yii::t('user', 'Username'),
            'firstname' => Yii::t('userextended', 'Firstname'),
            'lastname' => Yii::t('userextended', 'Lastname'),
            'birthday' => Yii::t('userextended', 'Birthday'),
            'roles' => Yii::t('userextended', 'Roles'),
            'email' => Yii::t('user', 'Email'),
            'registration_ip' => Yii::t('user', 'Registration ip'),
            'unconfirmed_email' => Yii::t('user', 'New email'),
            'password' => Yii::t('user', 'Password'),
            'created_at' => Yii::t('user', 'Registration time'),
            'confirmed_at' => Yii::t('user', 'Confirmation time'),
            'last_login_at' => Yii::t('userextended', 'Last login'),
        ];
    }

	/**
	 * If onlyEmail is true, username is email
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function beforeValidate()
	{
		if(Yii::$app->getModule('userextended')->onlyEmail) {
			$this->username = $this->email;
		}

		return parent::beforeValidate();
	}

	/**
     * @return ActiveQuery
     */
    public function getProfile()
    {
        return $this->hasOne($this->module->modelMap['Profile'], ['user_id' => 'id'])->from(Profile::tableName() . ' AS profile');
    }

    /**
     * @return ActiveQuery
     */
    public function getRoles()
    {
        return $this->hasMany(Assignment::class, ['user_id' => 'id'])->from(Assignment::tableName() . ' AS role');
    }

    /**
     * @return yii\rbac\Assignment[]
     */
    public function getCurrentUserRolesAssigned()
    {
        return Yii::$app->authManager->getAssignments(Yii::$app->user->id);
    }

    /**
     * @return Role[]
     */
    public function getCurrentUserRoles()
    {
        return Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);
    }

	/**
	 * Get Fullname
	 *
	 * @return string
	 */
	public function getFullName()
    {
    	return Html::encode($this->profile->firstname).' '.Html::encode($this->profile->lastname);
    }

	/**
	 * Get Rules by UserID
	 *
	 * @param string|int $userid
     *
	 * @return Role[]
	 */
    public function getRulesByUserID($userid)
    {
        return Yii::$app->authManager->getRolesByUser($userid);
    }

	/**
	 * Set Role to User
	 *
	 * @param $role
	 *
	 * @throws Exception
	 * @throws InvalidArgumentException
	 */
    public function setRole($role)
    {
	    $auth = Yii::$app->authManager;
	    $roleObject = $auth->getRole($role);

	    if (!$roleObject) {
		    throw new InvalidArgumentException ("There is no role \"$role\".");
	    }

	    $auth->assign($roleObject, $this->id);
    }

	/**
	 * Array roles for roles column in admin index
	 *
	 * @return array []
	 * @throws Exception
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

	/**
	 * Get Avatar Url
	 *
	 * @return string
	 */
    public function getAvatar()
    {
	    $profile = $this->getProfile()->one();
        $avatar = $profile->avatar ?: 'default.png';

        return Yii::getAlias(Yii::$app->getModule('userextended')->avatarURL).'small/'.$avatar;
    }
}

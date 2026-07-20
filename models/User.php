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
use dektrium\user\helpers\Password;
use dektrium\user\models\Token;
use dektrium\user\models\User as BaseUser;
use yii\base\InvalidArgumentException;
use yii\db\ActiveQuery;
use yii\helpers\Html;
use yii\rbac\Role;

/**
 * User ActiveRecord extends \dektrium\user\models\User
 *
 * @property int|null $password_changed_at
 * @property AuthAssignment[] $roles
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
	public function rules()
	{
		$rules = parent::rules();

		// Replace weak min-length with package policy (hashing stays on Password::hash)
		unset($rules['passwordLength']);
		$policy = PasswordPolicy::rule('password');
		$policy['on'] = ['register', 'create', 'update', 'settings'];
		$rules['passwordPolicy'] = $policy;

		return $rules;
	}

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
			'password_changed_at' => Yii::t('userextended', 'Password changed at'),
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
		if (Yii::$app->getModule('userextended')->onlyEmail) {
			$this->username = $this->email;
		}

		return parent::beforeValidate();
	}

	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (!parent::beforeSave($insert)) {
			return false;
		}

		if (!$this->hasAttribute('password_changed_at')) {
			return true;
		}

		if (!empty($this->password)) {
			// Parent already hashed via Password::hash — only stamp change time
			$this->setAttribute('password_changed_at', time());
		} elseif ($insert && $this->getAttribute('password_changed_at') === null) {
			$this->setAttribute('password_changed_at', time());
		}

		return true;
	}

	/**
	 * @inheritdoc
	 * Use policy-compliant generated passwords (Dektrium generate(8) can fail min length / special).
	 */
	public function create()
	{
		if ($this->getIsNewRecord() == false) {
			throw new \RuntimeException('Calling "' . __CLASS__ . '::' . __METHOD__ . '" on existing user');
		}

		$transaction = $this->getDb()->beginTransaction();

		try {
			$this->password = ($this->password == null && $this->module->enableGeneratingPassword)
				? PasswordPolicy::generate()
				: $this->password;

			$this->trigger(self::BEFORE_CREATE);

			if (!$this->save()) {
				$transaction->rollBack();
				return false;
			}

			$this->confirm();

			// Never pass showPassword=true unless mailPlaintextPasswords is explicitly enabled
			$showPassword = (bool) ModuleConfig::get('mailPlaintextPasswords', false);
			$this->mailer->sendWelcomeMessage($this, null, $showPassword);
			$this->trigger(self::AFTER_CREATE);

			$transaction->commit();

			return true;
		} catch (\Exception $e) {
			$transaction->rollBack();
			Yii::warning($e->getMessage());
			throw $e;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function register()
	{
		if ($this->getIsNewRecord() == false) {
			throw new \RuntimeException('Calling "' . __CLASS__ . '::' . __METHOD__ . '" on existing user');
		}

		$transaction = $this->getDb()->beginTransaction();

		try {
			$this->confirmed_at = $this->module->enableConfirmation ? null : time();
			$this->password = $this->module->enableGeneratingPassword
				? PasswordPolicy::generate()
				: $this->password;

			$this->trigger(self::BEFORE_REGISTER);

			if (!$this->save()) {
				$transaction->rollBack();
				return false;
			}

			if ($this->module->enableConfirmation) {
				$token = Yii::createObject([
					'class' => \dektrium\user\models\Token::class,
					'type' => \dektrium\user\models\Token::TYPE_CONFIRMATION,
				]);
				$token->link('user', $this);
			}

			$this->mailer->sendWelcomeMessage($this, isset($token) ? $token : null);
			$this->trigger(self::AFTER_REGISTER);

			$transaction->commit();

			return true;
		} catch (\Exception $e) {
			$transaction->rollBack();
			Yii::warning($e->getMessage());
			throw $e;
		}
	}

	/**
	 * Admin "resend password": never email plaintext by default.
	 * Rotates to a random password (old login stops working) and sends a recovery link.
	 *
	 * Set userextended.mailPlaintextPasswords=true only for legacy plaintext mail (not recommended).
	 *
	 * @return bool
	 */
	public function resendPassword()
	{
		if (!ModuleConfig::get('mailPlaintextPasswords', false)) {
			return $this->sendSecurePasswordResetLink();
		}

		$this->password = PasswordPolicy::generate();
		$attributes = ['password_hash'];
		if ($this->hasAttribute('password_changed_at')) {
			$this->password_changed_at = time();
			$attributes[] = 'password_changed_at';
		}
		$this->save(false, $attributes);

		return $this->mailer->sendGeneratedPassword($this, $this->password);
	}

	/**
	 * Invalidate current password and email a recovery link (no plaintext secret).
	 *
	 * Order matters: send the link first; only then rotate the password. If mail fails,
	 * the user is not locked out. Previous recovery tokens for this user are revoked.
	 *
	 * @return bool
	 */
	protected function sendSecurePasswordResetLink()
	{
		Token::deleteAll([
			'user_id' => $this->id,
			'type' => Token::TYPE_RECOVERY,
		]);

		/** @var Token $token */
		$token = Yii::createObject([
			'class' => Token::class,
			'user_id' => $this->id,
			'type' => Token::TYPE_RECOVERY,
		]);
		if (!$token->save(false)) {
			return false;
		}

		$sent = (bool) $this->mailer->sendRecoveryMessage($this, $token);
		if (!$sent) {
			$token->delete();
			return false;
		}

		// Mail delivered: invalidate old password so only the reset link can restore access
		$this->password = PasswordPolicy::generate();
		$attributes = ['password_hash'];
		if ($this->hasAttribute('password_changed_at')) {
			$this->password_changed_at = time();
			$attributes[] = 'password_changed_at';
		}
		if (!$this->save(false, $attributes)) {
			// Link already sent — keep token; admin can retry; user can still use link once password save is fixed
			Yii::warning('Recovery link sent but password rotation failed for user ' . (int) $this->id, __METHOD__);
			return false;
		}
		$this->password = null;

		SecurityAudit::log('admin_password_reset_link', (int) $this->id, [
			'username' => SecurityAudit::safeLogin($this->username),
		], 'admin', 'User', '/user/admin/resend-password');

		return true;
	}

	/**
	 * @inheritdoc
	 * Always hash via Dektrium/Yii Password helper.
	 */
	public function resetPassword($password)
	{
		$attributes = [
			'password_hash' => Password::hash($password),
		];
		if ($this->hasAttribute('password_changed_at')) {
			$attributes['password_changed_at'] = time();
		}

		return (bool) $this->updateAttributes($attributes);
	}

	/**
	 * Whether the password must be changed (age policy).
	 *
	 * @return bool
	 */
	public function isPasswordExpired()
	{
		$days = (int) ModuleConfig::get('passwordMaxAgeDays', 0);
		if ($days <= 0 || !$this->hasAttribute('password_changed_at')) {
			return false;
		}

		$changedAt = $this->getAttribute('password_changed_at');
		if ($changedAt === null || $changedAt === '' || (int) $changedAt === 0) {
			$changedAt = $this->created_at;
		}
		if ($changedAt === null || (int) $changedAt === 0) {
			return true;
		}

		return (time() - (int) $changedAt) >= ($days * 86400);
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
		return $this->hasMany(AuthAssignment::class, ['user_id' => 'id']);
	}

	/**
	 * @return \yii\rbac\Assignment[]
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
		return Html::encode($this->profile->firstname) . ' ' . Html::encode($this->profile->lastname);
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
	 * @param string $role
	 *
	 * @throws Exception
	 * @throws InvalidArgumentException
	 */
	public function setRole($role)
	{
		$auth = Yii::$app->authManager;
		$roleObject = $auth->getRole($role);

		if (!$roleObject) {
			throw new InvalidArgumentException("There is no role \"$role\".");
		}

		$auth->assign($roleObject, $this->id);
	}

	/**
	 * Array roles for roles column in admin index
	 *
	 * @return array []
	 */
	public function getRolesHTML()
	{
		$roles = [];
		foreach ($this->roles as $assignment) {
			$roles[] = ['item_name' => $assignment->item_name];
		}

		return $roles;
	}

	/**
	 * Get Avatar Url
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function getAvatar()
	{
		$profile = $this->profile;
		$avatar = ($profile && $profile->avatar) ? $profile->avatar : 'default.png';

		return Yii::getAlias(ModuleConfig::get('avatarURL')) . 'small/' . $avatar;
	}
}

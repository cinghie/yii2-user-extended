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

use Yii;
use cinghie\userextended\helpers\LoginRateLimiter;
use cinghie\userextended\helpers\ModuleConfig;
use cinghie\userextended\helpers\TurnstileVerifier;
use dektrium\user\helpers\Password;
use dektrium\user\models\LoginForm as BaseLoginForm;

/**
 * Class LoginForm
 */
class LoginForm extends BaseLoginForm
{
	/**
	 * @var string|null
	 */
	public $captcha;

	/**
	 * Cloudflare Turnstile token (also read from cf-turnstile-response).
	 *
	 * @var string|null
	 */
	public $turnstileToken;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = [
			'loginTrim' => ['login', 'trim'],
			'requiredFields' => [['login'], 'required'],
			'rateLimitValidate' => [
				'login',
				function ($attribute) {
					$limiter = LoginRateLimiter::create();
					if ($limiter->isLocked($this->login)) {
						$this->addError(
							$attribute,
							Yii::t('userextended', 'Too many failed login attempts. Please try again later.')
						);
					}
				},
			],
			'turnstileRequired' => [
				'turnstileToken',
				'required',
				'when' => function () {
					return $this->isTurnstileRequired();
				},
				'message' => Yii::t('userextended', 'Incorrect Username or Password'),
			],
			'turnstileValidate' => [
				'turnstileToken',
				function ($attribute) {
					if (!$this->isTurnstileRequired() || $this->hasErrors($attribute)) {
						return;
					}
					if (!TurnstileVerifier::verify($this->$attribute)) {
						$this->addError($attribute, Yii::t('userextended', 'Incorrect Username or Password'));
					}
				},
			],
			'confirmationValidate' => [
				'login',
				function ($attribute) {
					if ($this->user !== null) {
						$confirmationRequired = $this->module->enableConfirmation
							&& !$this->module->enableUnconfirmedLogin;
						if ($confirmationRequired && !$this->user->getIsConfirmed()) {
							// Generic message: avoid account enumeration
							$this->addError($attribute, Yii::t('userextended', 'Incorrect Username or Password'));
						}
						if ($this->user->getIsBlocked()) {
							$this->addError($attribute, Yii::t('userextended', 'Incorrect Username or Password'));
						}
					}
				}
			],
			'rememberMe' => ['rememberMe', 'boolean'],
			'captchaTrim' => ['captcha', 'trim'],
			'captchaRequired' => [
				'captcha',
				'required',
				'when' => function () {
					return $this->isCaptchaRequired();
				},
				'message' => Yii::t('userextended', 'Captcha verification is required.'),
			],
			'captchaValidate' => [
				'captcha',
				'captcha',
				'captchaAction' => $this->getCaptchaAction(),
				'when' => function () {
					return $this->isCaptchaRequired();
				},
			],
		];

		if (!$this->module->debug) {
			$rules = array_merge($rules, [
				'requiredFields' => [['login', 'password'], 'required'],
				'passwordValidate' => [
					'password',
					function ($attribute) {
						if ($this->hasErrors('login') || $this->hasErrors('turnstileToken')) {
							return;
						}
						if ($this->user === null || !Password::validate($this->password, $this->user->password_hash)) {
							$this->addError($attribute, Yii::t('userextended', 'Incorrect Username or Password'));
						}
					}
				]
			]);
		}

		return $rules;
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		$labels = parent::attributeLabels();
		$labels['captcha'] = Yii::t('userextended', 'Captcha');
		$labels['turnstileToken'] = Yii::t('userextended', 'Security check');

		return $labels;
	}

	/**
	 * @inheritdoc
	 */
	public function beforeValidate()
	{
		if ($this->isTurnstileRequired()) {
			$posted = Yii::$app->request->post('cf-turnstile-response');
			if (is_string($posted) && $posted !== '') {
				$this->turnstileToken = $posted;
			}
		}

		return parent::beforeValidate();
	}

	/**
	 * Whether captcha must be shown/validated for the current IP/login.
	 *
	 * @return bool
	 */
	public function isCaptchaRequired()
	{
		return LoginRateLimiter::create()->requiresCaptcha($this->login);
	}

	/**
	 * Whether Cloudflare Turnstile must be validated.
	 *
	 * @return bool
	 */
	public function isTurnstileRequired()
	{
		return TurnstileVerifier::isEnabledForLogin();
	}

	/**
	 * @return string|array
	 */
	protected function getCaptchaAction()
	{
		$action = ModuleConfig::get('loginCaptchaAction');

		return $action ?: ['/site/captcha'];
	}

	/**
	 * @inheritdoc
	 */
	public function login()
	{
		$limiter = LoginRateLimiter::create();

		if ($limiter->isEnabled() && $limiter->isLocked($this->login)) {
			$this->addError(
				'login',
				Yii::t('userextended', 'Too many failed login attempts. Please try again later.')
			);
			return false;
		}

		$loggedIn = parent::login();
		if ($loggedIn) {
			$limiter->clear($this->login);
		}

		return $loggedIn;
	}
}

<?php

namespace cinghie\userextended\tests\unit;

use Yii;
use cinghie\userextended\helpers\LoginRateLimiter;
use cinghie\userextended\models\LoginForm;
use cinghie\userextended\tests\TestCase;

/**
 * LoginForm failure counting must not depend on translated lock messages (i18n-safe).
 */
class LoginFormRateLimitI18nTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->mockApplication([
			'enableLoginRateLimit' => true,
			'loginMaxAttempts' => 2,
			'loginLockoutDuration' => 120,
			'loginProgressiveDelay' => false,
			'loginCaptchaAfterAttempts' => 0,
			'enableCloudflareTurnstile' => false,
			'enablePasswordPolicy' => false,
		], [
			'language' => 'it',
			'modules' => [
				'user' => [
					'class' => \dektrium\user\Module::class,
					'enableRegistration' => false,
					'modelMap' => [
						'LoginForm' => LoginForm::class,
						'User' => \cinghie\userextended\models\User::class,
					],
				],
			],
		]);
		$_SERVER['REMOTE_ADDR'] = '198.51.100.20';
	}

	public function testLockedAccountDoesNotCountAsNewFailureUnderItalianLocale(): void
	{
		Yii::$app->language = 'it';

		$limiter = LoginRateLimiter::create();
		$limiter->recordFailure('demo');
		$limiter->recordFailure('demo');
		$this->assertTrue($limiter->isLocked('demo'));

		$model = Yii::createObject(LoginForm::class);
		$model->login = 'demo';
		$model->password = 'x';
		$model->addError(
			'login',
			Yii::t('userextended', 'Too many failed login attempts. Please try again later.')
		);

		$this->assertFalse(
			$model->shouldCountAsLoginFailure(),
			'Locked failures must not increment counters regardless of translated message text'
		);
	}
}

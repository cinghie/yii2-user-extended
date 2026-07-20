<?php

namespace cinghie\userextended\tests\unit;

use Yii;
use cinghie\userextended\helpers\RecoveryRateLimiter;
use cinghie\userextended\helpers\UserModuleHardening;
use cinghie\userextended\tests\TestCase;
use dektrium\user\Module as UserModule;

class RecoveryHardeningTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->mockApplication([
			'enableRecoveryRateLimit' => true,
			'recoveryMaxAttempts' => 3,
			'recoveryLockoutDuration' => 120,
			'recoveryProgressiveDelay' => false,
			'recoverWithin' => 3600,
			'confirmWithin' => 21600,
			'enableSecureEmailChange' => true,
			'mailPlaintextPasswords' => false,
			'rateLimitStorage' => 'cache',
		], [
			'modules' => [
				'user' => [
					'class' => UserModule::class,
					'cost' => 10,
					'enableRegistration' => false,
					'enablePasswordRecovery' => true,
					'recoverWithin' => 21600,
					'confirmWithin' => 86400,
					'emailChangeStrategy' => UserModule::STRATEGY_DEFAULT,
					'enableGeneratingPassword' => true,
				],
			],
		]);
		$_SERVER['REMOTE_ADDR'] = '203.0.113.77';
	}

	public function testUserModuleHardeningAppliesSafeDefaults(): void
	{
		$userModule = Yii::$app->getModule('user');
		$ue = Yii::$app->getModule('userextended');
		UserModuleHardening::apply($userModule, $ue);

		$this->assertSame(3600, (int) $userModule->recoverWithin);
		$this->assertSame(21600, (int) $userModule->confirmWithin);
		$this->assertSame(UserModule::STRATEGY_SECURE, (int) $userModule->emailChangeStrategy);
		$this->assertFalse((bool) $userModule->enableGeneratingPassword);
	}

	public function testRecoveryRateLimiterLocks(): void
	{
		$limiter = RecoveryRateLimiter::create();
		$this->assertFalse($limiter->isLocked('a@example.test'));
		$limiter->recordAttempt('a@example.test');
		$limiter->recordAttempt('a@example.test');
		$this->assertFalse($limiter->isLocked('a@example.test'));
		$limiter->recordAttempt('a@example.test');
		$this->assertTrue($limiter->isLocked('a@example.test'));
	}

	public function testMailPlaintextPasswordsDefaultOff(): void
	{
		$ue = Yii::$app->getModule('userextended');
		$this->assertFalse((bool) $ue->mailPlaintextPasswords);
	}
}

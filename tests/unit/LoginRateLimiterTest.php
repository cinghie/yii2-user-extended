<?php

namespace cinghie\userextended\tests\unit;

use Yii;
use cinghie\userextended\helpers\LoginRateLimiter;
use cinghie\userextended\tests\TestCase;

class LoginRateLimiterTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->mockApplication([
			'enableLoginRateLimit' => true,
			'loginMaxAttempts' => 3,
			'loginLockoutDuration' => 120,
			'loginProgressiveDelay' => false,
		]);
		Yii::$app->request->setBodyParams([]);
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
	}

	public function testLocksAfterMaxFailures(): void
	{
		$limiter = LoginRateLimiter::create();
		$this->assertFalse($limiter->isLocked('alice'));

		$limiter->recordFailure('alice');
		$limiter->recordFailure('alice');
		$this->assertFalse($limiter->isLocked('alice'));

		$limiter->recordFailure('alice');
		$this->assertTrue($limiter->isLocked('alice'));
		$this->assertGreaterThan(0, $limiter->getRemainingLockSeconds('alice'));
	}

	public function testClearRemovesLock(): void
	{
		$limiter = LoginRateLimiter::create();
		for ($i = 0; $i < 3; $i++) {
			$limiter->recordFailure('bob');
		}
		$this->assertTrue($limiter->isLocked('bob'));
		$limiter->clear('bob');
		$this->assertFalse($limiter->isLocked('bob'));
	}
}

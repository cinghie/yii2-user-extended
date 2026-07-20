<?php

namespace cinghie\userextended\tests\unit;

use Yii;
use cinghie\userextended\helpers\LoginRateLimiter;
use cinghie\userextended\helpers\RateLimitStore;
use cinghie\userextended\tests\TestCase;

/**
 * When rateLimitStorage=db but the table is missing, store must fall back to cache.
 */
class RateLimitStoreFallbackTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->mockApplication([
			'enableLoginRateLimit' => true,
			'rateLimitStorage' => 'db',
			'loginMaxAttempts' => 3,
			'loginLockoutDuration' => 120,
			'loginProgressiveDelay' => false,
		]);
		RateLimitStore::flushRuntime();
		$_SERVER['REMOTE_ADDR'] = '203.0.113.55';
	}

	public function testFallsBackToCacheWithoutTable(): void
	{
		// No db component / no table → isDbReady false → cache path
		$limiter = LoginRateLimiter::create();
		for ($i = 0; $i < 3; $i++) {
			$limiter->recordFailure('fallback-user');
		}
		$this->assertTrue($limiter->isLocked('fallback-user'));

		Yii::$app->cache->flush();
		RateLimitStore::flushRuntime();
		$this->assertFalse(LoginRateLimiter::create()->isLocked('fallback-user'));
	}
}

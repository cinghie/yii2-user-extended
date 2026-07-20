<?php

namespace cinghie\userextended\tests\unit;

use Yii;
use cinghie\userextended\helpers\LoginRateLimiter;
use cinghie\userextended\helpers\RateLimitStore;
use cinghie\userextended\helpers\RegistrationRateLimiter;
use cinghie\userextended\tests\TestCase;
use yii\db\Connection;
use yii\db\Schema;

class DbRateLimitStoreTest extends TestCase
{
	protected function setUp(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite required for DB rate-limit tests');
		}

		parent::setUp();
		$this->mockApplication(
			[
				'enableLoginRateLimit' => true,
				'enableRegistrationRateLimit' => true,
				'rateLimitStorage' => 'db',
				'loginMaxAttempts' => 3,
				'loginLockoutDuration' => 300,
				'loginProgressiveDelay' => false,
				'registrationMaxAttempts' => 3,
				'registrationLockoutDuration' => 300,
				'registrationProgressiveDelay' => false,
			],
			[
				'components' => [
					'db' => [
						'class' => Connection::class,
						'dsn' => 'sqlite::memory:',
					],
					'cache' => [
						'class' => \yii\caching\ArrayCache::class,
					],
				],
			]
		);

		$this->createRateLimitTable();
		RateLimitStore::flushRuntime();
		$_SERVER['REMOTE_ADDR'] = '198.51.100.20';
	}

	protected function createRateLimitTable(): void
	{
		$db = Yii::$app->db;
		$db->createCommand()->createTable('{{%userextended_rate_limit}}', [
			'rate_key' => Schema::TYPE_STRING . '(191) NOT NULL PRIMARY KEY',
			'attempt_count' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 0',
			'locked_until' => Schema::TYPE_INTEGER . ' NULL',
			'expires_at' => Schema::TYPE_INTEGER . ' NOT NULL',
			'updated_at' => Schema::TYPE_INTEGER . ' NOT NULL',
		])->execute();
	}

	public function testLoginLockSurvivesCacheFlush(): void
	{
		$limiter = LoginRateLimiter::create();
		for ($i = 0; $i < 3; $i++) {
			$limiter->recordFailure('db-user');
		}
		$this->assertTrue($limiter->isLocked('db-user'));

		Yii::$app->cache->flush();
		RateLimitStore::flushRuntime();

		$again = LoginRateLimiter::create();
		$this->assertTrue($again->isLocked('db-user'));
		$this->assertGreaterThan(0, $again->getRemainingLockSeconds('db-user'));
	}

	public function testRegistrationLockSurvivesCacheFlush(): void
	{
		$limiter = RegistrationRateLimiter::create();
		for ($i = 0; $i < 3; $i++) {
			$limiter->recordAttempt('victim@example.test');
		}
		$this->assertTrue($limiter->isLocked('victim@example.test'));

		Yii::$app->cache->flush();
		RateLimitStore::flushRuntime();

		$again = RegistrationRateLimiter::create();
		$this->assertTrue($again->isLocked('victim@example.test'));
	}

	public function testClearRemovesDbRow(): void
	{
		$limiter = LoginRateLimiter::create();
		for ($i = 0; $i < 3; $i++) {
			$limiter->recordFailure('clear-me');
		}
		$this->assertTrue($limiter->isLocked('clear-me'));
		$limiter->clear('clear-me');
		Yii::$app->cache->flush();
		RateLimitStore::flushRuntime();
		$this->assertFalse(LoginRateLimiter::create()->isLocked('clear-me'));
	}

	public function testExpiredRowIsIgnored(): void
	{
		$key = LoginRateLimiter::KEY_PREFIX . 'user.' . hash('sha256', 'expired-user');
		$past = time() - 10;
		Yii::$app->db->createCommand()->insert('{{%userextended_rate_limit}}', [
			'rate_key' => $key,
			'attempt_count' => 99,
			'locked_until' => time() + 600,
			'expires_at' => $past,
			'updated_at' => $past,
		])->execute();

		RateLimitStore::flushRuntime();
		$this->assertNull(RateLimitStore::get($key));
		$this->assertFalse(LoginRateLimiter::create()->isLocked('expired-user'));
	}

	public function testUpsertOverwritesExistingRow(): void
	{
		$key = 'userextended.test.upsert';
		RateLimitStore::set($key, ['count' => 1, 'locked_until' => null], 120);
		RateLimitStore::set($key, ['count' => 5, 'locked_until' => time() + 60], 120);
		$data = RateLimitStore::get($key);
		$this->assertNotNull($data);
		$this->assertSame(5, $data['count']);
		$this->assertNotNull($data['locked_until']);
	}
}

<?php

namespace cinghie\userextended\tests\unit;

use Yii;
use cinghie\userextended\helpers\SecurityAudit;
use cinghie\userextended\tests\TestCase;
use yii\log\Logger;

/**
 * SecurityAudit sanitization and enable flags.
 */
class SecurityAuditTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->mockApplication([
			'enableSecurityAudit' => true,
			'enableRbacAssignmentAudit' => true,
		]);
		Yii::getLogger()->flush(true);
		Yii::getLogger()->messages = [];
	}

	public function testSanitizeDropsSecretsFromPayload(): void
	{
		SecurityAudit::log('test_sanitize', 1, [
			'ok' => 'keep-me',
			'password' => 'must-not-appear',
			'token' => 'must-not-appear',
			'cloudflareSecretKey' => 'must-not-appear',
			'nested' => [
				'api_key' => 'must-not-appear',
				'role' => 'admin',
			],
		], 'test', 'User', '/test');

		$payload = $this->lastSecurityInfoMessage();
		$this->assertNotNull($payload);
		$this->assertSame('keep-me', $payload['ok'] ?? null);
		$this->assertSame('admin', $payload['nested']['role'] ?? null);
		$this->assertArrayNotHasKey('password', $payload);
		$this->assertArrayNotHasKey('token', $payload);
		$this->assertArrayNotHasKey('cloudflareSecretKey', $payload);
		$this->assertArrayNotHasKey('api_key', $payload['nested'] ?? []);
		$this->assertSame('test_sanitize', $payload['action'] ?? null);
	}

	public function testRbacActionsHonourEnableRbacAssignmentAudit(): void
	{
		$this->mockApplication([
			'enableSecurityAudit' => true,
			'enableRbacAssignmentAudit' => false,
		]);
		Yii::getLogger()->flush(true);
		Yii::getLogger()->messages = [];

		SecurityAudit::log('assign_update', 2, ['added' => ['admin']], 'rbac', 'User', '/user/admin/assignments');
		$this->assertNull($this->lastSecurityInfoMessage());

		SecurityAudit::log('login_success', 2, ['login' => 'demo'], 'auth', 'User', '/user/security/login');
		$this->assertNotNull($this->lastSecurityInfoMessage());
	}

	public function testSafeLoginTruncates(): void
	{
		$long = str_repeat('a', 250) . '@example.test';
		$safe = SecurityAudit::safeLogin($long);
		$this->assertSame(190, mb_strlen($safe));
		$this->assertStringStartsWith('aaa', $safe);
	}

	/**
	 * @return array|null
	 */
	private function lastSecurityInfoMessage()
	{
		$messages = Yii::getLogger()->messages;
		for ($i = count($messages) - 1; $i >= 0; $i--) {
			$message = $messages[$i];
			if (($message[2] ?? null) === 'userextended.security' && ($message[1] ?? null) === Logger::LEVEL_INFO) {
				return is_array($message[0]) ? $message[0] : null;
			}
		}

		return null;
	}
}

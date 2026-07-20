<?php

namespace cinghie\userextended\tests\unit;

use cinghie\userextended\helpers\TurnstileVerifier;
use cinghie\userextended\tests\TestCase;

class TurnstileVerifierTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->mockApplication([
			'enableCloudflareTurnstile' => true,
			'cloudflareSiteKey' => 'site-test',
			'cloudflareSecretKey' => 'secret-test',
		]);
	}

	public function testMissingTokenFailsClosed(): void
	{
		$this->assertFalse(TurnstileVerifier::verify(null));
		$this->assertFalse(TurnstileVerifier::verify(''));
		$this->assertFalse(TurnstileVerifier::verify('   '));
	}

	public function testInvalidTokenFailsClosed(): void
	{
		TurnstileVerifier::$siteVerifyHandler = static function () {
			return ['success' => false, 'error-codes' => ['invalid-input-response']];
		};
		$this->assertFalse(TurnstileVerifier::verify('bad-token'));
	}

	public function testValidTokenPasses(): void
	{
		TurnstileVerifier::$siteVerifyHandler = static function (array $payload) {
			\PHPUnit\Framework\Assert::assertSame('secret-test', $payload['secret']);
			\PHPUnit\Framework\Assert::assertSame('good-token', $payload['response']);
			return ['success' => true];
		};
		$this->assertTrue(TurnstileVerifier::verify('good-token'));
	}

	public function testHttpFailureFailsClosed(): void
	{
		TurnstileVerifier::$siteVerifyHandler = static function () {
			return null;
		};
		$this->assertFalse(TurnstileVerifier::verify('any-token'));
	}
}

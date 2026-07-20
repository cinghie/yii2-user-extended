<?php

namespace cinghie\userextended\tests\unit;

use cinghie\userextended\helpers\ModuleConfig;
use cinghie\userextended\helpers\PasswordPolicy;
use cinghie\userextended\tests\TestCase;

class PasswordPolicyTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->mockApplication([
			'enablePasswordPolicy' => true,
			'passwordMinLength' => 8,
			'passwordRequireUppercase' => true,
			'passwordRequireLowercase' => true,
			'passwordRequireDigit' => true,
			'passwordRequireSpecial' => false,
			'passwordBanCommon' => true,
		]);
		ModuleConfig::flush();
	}

	public function testRejectsShortAndCommon(): void
	{
		$this->assertNotEmpty(PasswordPolicy::check('abc'));
		$this->assertNotEmpty(PasswordPolicy::check('password'));
	}

	public function testAcceptsStrongPassword(): void
	{
		$this->assertSame([], PasswordPolicy::check('Str0ngPass'));
	}
}

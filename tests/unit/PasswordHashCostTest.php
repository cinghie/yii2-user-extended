<?php

namespace cinghie\userextended\tests\unit;

use Yii;
use cinghie\userextended\helpers\PasswordHashCost;
use cinghie\userextended\tests\TestCase;
use dektrium\user\helpers\Password;
use dektrium\user\Module as UserModule;
use yii\base\Module as BaseModule;

class PasswordHashCostTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->mockApplication([
			'passwordHashCost' => 13,
			'rehashPasswordOnLogin' => true,
			'enableSecurityAudit' => false,
		], [
			'modules' => [
				'user' => [
					'class' => UserModule::class,
					'cost' => 10,
					'enableRegistration' => false,
				],
			],
		]);
	}

	public function testClampNeverBelowTwelve(): void
	{
		$this->assertSame(12, PasswordHashCost::clamp(4));
		$this->assertSame(12, PasswordHashCost::clamp(10));
		$this->assertSame(15, PasswordHashCost::clamp(99));
		$this->assertSame(13, PasswordHashCost::clamp(13));
	}

	public function testNeedsRehashDetectsWeakCostOnly(): void
	{
		$weak = password_hash('Secret1a!', PASSWORD_BCRYPT, ['cost' => 10]);
		$strong = password_hash('Secret1a!', PASSWORD_BCRYPT, ['cost' => 13]);
		$stronger = password_hash('Secret1a!', PASSWORD_BCRYPT, ['cost' => 14]);

		$this->assertTrue(PasswordHashCost::needsRehash($weak, 13));
		$this->assertFalse(PasswordHashCost::needsRehash($strong, 13));
		$this->assertFalse(PasswordHashCost::needsRehash($stronger, 13));
		$this->assertTrue(Password::validate('Secret1a!', $weak));
	}

	public function testApplyToUserModuleRaisesButDoesNotLower(): void
	{
		$userModule = Yii::$app->getModule('user');
		$this->assertInstanceOf(BaseModule::class, $userModule);

		$userModule->cost = 10;
		PasswordHashCost::applyToUserModule($userModule);
		$this->assertSame(13, (int) $userModule->cost);

		$userModule->cost = 14;
		PasswordHashCost::applyToUserModule($userModule);
		$this->assertSame(14, (int) $userModule->cost);
	}

	public function testHashUsesRaisedCost(): void
	{
		$userModule = Yii::$app->getModule('user');
		PasswordHashCost::applyToUserModule($userModule);
		$hash = Password::hash('Secret1a!');
		$this->assertMatchesRegularExpression('/^\$2[axy]\$13\$/', $hash);
	}

	public function testRehashIfNeededRejectsWrongPassword(): void
	{
		$user = $this->fakeUser(1, password_hash('Secret1a!', PASSWORD_BCRYPT, ['cost' => 10]));
		$this->assertFalse(PasswordHashCost::rehashIfNeeded($user, 'WrongPass1!'));
		$this->assertSame(10, PasswordHashCost::costFromHash($user->getAttribute('password_hash')));
	}

	public function testRehashIfNeededUpgradesMatchingPassword(): void
	{
		$plain = 'Secret1a!';
		$user = $this->fakeUser(2, password_hash($plain, PASSWORD_BCRYPT, ['cost' => 10]));

		PasswordHashCost::applyToUserModule(Yii::$app->getModule('user'));
		$this->assertTrue(PasswordHashCost::rehashIfNeeded($user, $plain));
		$this->assertSame(13, PasswordHashCost::costFromHash($user->getAttribute('password_hash')));
		$this->assertTrue(Password::validate($plain, $user->getAttribute('password_hash')));
	}

	/**
	 * @return object{getAttribute:callable,updateAttributes:callable}
	 */
	private function fakeUser(int $id, string $hash): object
	{
		return new class($id, $hash) {
			private $attrs;

			public function __construct(int $id, string $hash)
			{
				$this->attrs = ['id' => $id, 'password_hash' => $hash];
			}

			public function getAttribute($name)
			{
				return $this->attrs[$name] ?? null;
			}

			public function updateAttributes($attributes)
			{
				foreach ($attributes as $k => $v) {
					$this->attrs[$k] = $v;
				}
				return 1;
			}
		};
	}
}

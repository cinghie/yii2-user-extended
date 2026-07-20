<?php

namespace cinghie\userextended\tests\unit;

use cinghie\userextended\Module;
use cinghie\userextended\helpers\ModuleSettings;
use cinghie\userextended\tests\TestCase;
use yii\base\InvalidConfigException;

class ModuleSettingsTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->mockApplication();
	}

	public function testClampsNegativeTimeout(): void
	{
		$module = \Yii::$app->getModule('userextended');
		$module->sessionTimeout = -10;
		$module->clientWarningBeforeExpire = 99999;
		ModuleSettings::validate($module);
		$this->assertSame(0, $module->sessionTimeout);
	}

	public function testTurnstileRequiresKeys(): void
	{
		$this->expectException(InvalidConfigException::class);
		$module = \Yii::$app->getModule('userextended');
		$module->enableCloudflareTurnstile = true;
		$module->cloudflareSiteKey = '';
		$module->cloudflareSecretKey = '';
		ModuleSettings::validate($module);
	}

	public function testSecurityPresetProd(): void
	{
		$preset = Module::securityPreset('prod');
		$this->assertSame(1800, $preset['sessionTimeout']);
		$this->assertTrue($preset['disableAutoLogin']);
		$this->assertTrue($preset['useAbsoluteAuthTimeout']);
	}

	public function testSoftPresetDoesNotOverrideExplicitConfig(): void
	{
		$this->destroyApplication();
		$this->mockApplication([
			'securityPreset' => 'prod',
			'sessionTimeout' => 9999,
		]);
		$module = \Yii::$app->getModule('userextended');
		$this->assertSame(9999, $module->sessionTimeout);
	}
}

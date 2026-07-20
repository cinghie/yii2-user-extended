<?php

namespace cinghie\userextended\tests;

use Yii;
use cinghie\userextended\Module;
use cinghie\userextended\helpers\ModuleConfig;
use cinghie\userextended\helpers\RateLimitStore;
use cinghie\userextended\helpers\TurnstileVerifier;
use PHPUnit\Framework\TestCase as BaseTestCase;
use yii\caching\ArrayCache;
use yii\helpers\ArrayHelper;
use yii\web\Application;
use yii\web\User;

/**
 * Boots a minimal Yii web application with the userextended module.
 */
abstract class TestCase extends BaseTestCase
{
	/**
	 * @param array $moduleConfig
	 * @param array $appConfig
	 */
	protected function mockApplication(array $moduleConfig = [], array $appConfig = []): void
	{
		$this->destroyApplication();
		ModuleConfig::flush();
		RateLimitStore::flushRuntime();
		TurnstileVerifier::$siteVerifyHandler = null;

		$config = ArrayHelper::merge([
			'id' => 'userextended-tests',
			'basePath' => dirname(__DIR__),
			'vendorPath' => dirname(dirname(dirname(__DIR__))),
			'components' => [
				'errorHandler' => [
					'class' => \cinghie\userextended\tests\SilentErrorHandler::class,
				],
				'cache' => [
					'class' => ArrayCache::class,
				],
				'session' => [
					'class' => \yii\web\Session::class,
					'useCookies' => false,
				],
				'request' => [
					'class' => \yii\web\Request::class,
					'cookieValidationKey' => 'test-key',
					'scriptFile' => __DIR__ . '/index.php',
					'scriptUrl' => '/index.php',
					'hostInfo' => 'https://example.test',
				],
				'user' => [
					'class' => User::class,
					'identityClass' => \yii\web\IdentityInterface::class,
					'enableSession' => false,
					'enableAutoLogin' => false,
				],
				'i18n' => [
					'translations' => [
						'*' => [
							'class' => \yii\i18n\PhpMessageSource::class,
							'sourceLanguage' => 'en',
							'basePath' => dirname(__DIR__) . '/messages',
						],
						'userextended' => [
							'class' => \yii\i18n\PhpMessageSource::class,
							'basePath' => dirname(__DIR__) . '/messages',
							'sourceLanguage' => 'en',
						],
					],
				],
				'log' => [
					'traceLevel' => 0,
					'targets' => [],
				],
			],
			'modules' => [
				'userextended' => ArrayHelper::merge([
					'class' => Module::class,
					'enableCloudflareTurnstile' => false,
					'enableLoginRateLimit' => true,
					'rateLimitStorage' => 'cache',
					'loginMaxAttempts' => 3,
					'loginLockoutDuration' => 60,
					'loginAttemptWindow' => 300,
					'loginProgressiveDelay' => false,
					'loginCaptchaAfterAttempts' => 0,
					'enablePasswordPolicy' => true,
					'passwordMinLength' => 8,
					'enableSecurityAudit' => false,
				], $moduleConfig),
			],
		], $appConfig);

		new Application($config);
		Yii::$app->session->open();
	}

	protected function destroyApplication(): void
	{
		TurnstileVerifier::$siteVerifyHandler = null;
		ModuleConfig::flush();
		RateLimitStore::flushRuntime();
		if (Yii::$app ?? null) {
			if (Yii::$app->has('session', true) && Yii::$app->session->getIsActive()) {
				Yii::$app->session->close();
			}
			Yii::$app = null;
		}
	}

	protected function tearDown(): void
	{
		$this->destroyApplication();
		parent::tearDown();
	}
}

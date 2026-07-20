<?php

namespace cinghie\userextended\tests\unit;

use Yii;
use cinghie\userextended\controllers\SecurityController;
use cinghie\userextended\models\LoginForm;
use cinghie\userextended\tests\TestCase;
use dektrium\user\Finder;

/**
 * Smoke: login with ?expired=1 sets the session-expired flash (client redirect target).
 */
class SessionExpireSmokeTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->mockApplication([
			'enableSecurityAudit' => false,
			'enableClientSessionExpireRedirect' => true,
			'sessionTimeout' => 3600,
			'enableLoginRateLimit' => false,
			'enableCloudflareTurnstile' => false,
		], [
			'modules' => [
				'user' => [
					'class' => \dektrium\user\Module::class,
					'enableRegistration' => false,
					'modelMap' => [
						'LoginForm' => LoginForm::class,
						'User' => \cinghie\userextended\models\User::class,
					],
					'controllerMap' => [
						'security' => SecurityController::class,
					],
				],
			],
		]);
	}

	public function testActionLoginWithExpiredQuerySetsFlash(): void
	{
		Yii::$app->request->setQueryParams(['expired' => '1']);
		Yii::$app->request->setBodyParams([]);

		$finder = Yii::createObject(Finder::class);
		$controller = $this->getMockBuilder(SecurityController::class)
			->setConstructorArgs(['security', Yii::$app->getModule('user'), $finder])
			->onlyMethods(['render', 'goHome', 'goBack'])
			->getMock();

		$controller->expects($this->once())
			->method('render')
			->willReturn('login-view');

		$result = $controller->actionLogin();
		$this->assertSame('login-view', $result);

		$flash = Yii::$app->session->getFlash('login', null, false);
		$this->assertNotEmpty($flash);
		$text = is_array($flash) ? implode(' ', $flash) : (string) $flash;
		$this->assertStringContainsStringIgnoringCase('expired', $text);
	}

	public function testLoginUrlContainsExpiredFlag(): void
	{
		$url = \yii\helpers\Url::to(['/user/security/login', 'expired' => 1]);
		$this->assertStringContainsString('expired', $url);
	}
}

<?php

namespace cinghie\userextended\tests\unit;

use Yii;
use cinghie\userextended\helpers\TurnstileVerifier;
use cinghie\userextended\models\RegistrationForm;
use cinghie\userextended\tests\TestCase;

/**
 * Registration Turnstile must not call siteverify during ActiveForm AJAX validation (single-use tokens),
 * but must still verify on forged XHR that is not ActiveForm field validation.
 */
class RegistrationFormTurnstileAjaxTest extends TestCase
{
	/** @var int */
	private $verifyCalls = 0;

	protected function setUp(): void
	{
		parent::setUp();
		$this->verifyCalls = 0;
		$this->mockApplication([
			'enableCloudflareTurnstile' => true,
			'cloudflareTurnstileOnRegistration' => true,
			'cloudflareSiteKey' => 'site-test',
			'cloudflareSecretKey' => 'secret-test',
			'enablePasswordPolicy' => false,
			'firstname' => false,
			'lastname' => false,
			'birthday' => false,
			'captcha' => false,
			'terms' => false,
		], [
			'modules' => [
				'user' => [
					'class' => \dektrium\user\Module::class,
					'enableRegistration' => true,
					'enableGeneratingPassword' => false,
					'modelMap' => [
						'RegistrationForm' => RegistrationForm::class,
						'User' => \cinghie\userextended\models\User::class,
					],
				],
			],
		]);

		TurnstileVerifier::$siteVerifyHandler = function () {
			$this->verifyCalls++;
			return ['success' => true];
		};
	}

	public function testActiveFormAjaxValidationDoesNotCallSiteVerify(): void
	{
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		Yii::$app->request->headers->set('X-Requested-With', 'XMLHttpRequest');
		Yii::$app->request->setBodyParams(['ajax' => 'register-form']);

		$model = Yii::createObject(RegistrationForm::class);
		$model->email = 'ajax-user@example.test';
		$model->username = 'ajaxuser';
		$model->password = 'Password1!';
		$model->turnstileToken = 'single-use-token';

		$model->validate(['turnstileToken']);

		$this->assertSame(0, $this->verifyCalls, 'ActiveForm AJAX must not consume the Turnstile token via siteverify');
		$this->assertFalse($model->hasErrors('turnstileToken'));
	}

	public function testForgedAjaxWithoutActiveFormIdStillCallsSiteVerify(): void
	{
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		Yii::$app->request->headers->set('X-Requested-With', 'XMLHttpRequest');
		Yii::$app->request->setBodyParams([]);

		$model = Yii::createObject(RegistrationForm::class);
		$model->email = 'forged-user@example.test';
		$model->username = 'forgeduser';
		$model->password = 'Password1!';
		$model->turnstileToken = 'single-use-token';

		$model->validate(['turnstileToken']);

		$this->assertSame(1, $this->verifyCalls, 'Forged XHR without ActiveForm ajax id must still verify Turnstile');
		$this->assertFalse($model->hasErrors('turnstileToken'));
	}

	public function testNonAjaxValidationCallsSiteVerify(): void
	{
		unset($_SERVER['HTTP_X_REQUESTED_WITH']);
		Yii::$app->request->headers->remove('X-Requested-With');
		Yii::$app->request->setBodyParams([]);

		$model = Yii::createObject(RegistrationForm::class);
		$model->email = 'post-user@example.test';
		$model->username = 'postuser';
		$model->password = 'Password1!';
		$model->turnstileToken = 'single-use-token';

		$model->validate(['turnstileToken']);

		$this->assertSame(1, $this->verifyCalls, 'Final POST must verify Turnstile');
		$this->assertFalse($model->hasErrors('turnstileToken'));
	}
}

<?php

namespace cinghie\userextended\tests\unit;

use Yii;
use cinghie\userextended\controllers\AdminController;
use cinghie\userextended\tests\TestCase;
use dektrium\user\Finder;
use yii\web\ForbiddenHttpException;

class AdminSwitchAccessTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->mockApplication([
			'enableSecurityAudit' => false,
		], [
			'modules' => [
				'user' => [
					'class' => \dektrium\user\Module::class,
					'enableRegistration' => false,
				],
			],
		]);
	}

	public function testSwitchAlwaysForbidden(): void
	{
		$finder = Yii::createObject(Finder::class);
		$controller = new AdminController('admin', Yii::$app->getModule('user'), $finder);

		$this->expectException(ForbiddenHttpException::class);
		$controller->actionSwitch(1);
	}
}

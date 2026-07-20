<?php

namespace cinghie\userextended\tests\unit;

use cinghie\userextended\models\UserSearch;
use cinghie\userextended\tests\TestCase;

class UserSearchRuleTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->mockApplication();
	}

	public function testRejectsSqlInjectionInRule(): void
	{
		$finder = \Yii::createObject(\dektrium\user\Finder::class);
		$search = new class($finder) extends UserSearch {
			public function getNameList()
			{
				return ['admin' => 'admin', 'user' => 'user'];
			}
		};

		$search->rule = "admin' OR '1'='1";
		$this->assertFalse($search->validate(['rule']), 'Malicious rule must fail the in-range validator');
		$this->assertTrue($search->hasErrors('rule'));
	}

	public function testAcceptsKnownRole(): void
	{
		$finder = \Yii::createObject(\dektrium\user\Finder::class);
		$search = new class($finder) extends UserSearch {
			public function getNameList()
			{
				return ['admin' => 'admin', 'user' => 'user'];
			}
		};

		$search->rule = 'admin';
		$this->assertTrue($search->validate(['rule']));
	}
}

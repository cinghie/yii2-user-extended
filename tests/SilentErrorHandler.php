<?php

namespace cinghie\userextended\tests;

use yii\web\ErrorHandler;

/**
 * ErrorHandler that does not register PHP handlers (keeps PHPUnit 10+ happy).
 */
class SilentErrorHandler extends ErrorHandler
{
	public function register()
	{
		// no-op
	}
}

<?php

/**
 * PHPUnit bootstrap for cinghie/yii2-user-extended.
 */

$packageRoot = dirname(__DIR__);

$autoloadCandidates = [
	$packageRoot . '/vendor/autoload.php',
	dirname($packageRoot, 3) . '/autoload.php', // .../vendor/cinghie/yii2-user-extended → .../vendor/autoload.php
	dirname($packageRoot, 2) . '/autoload.php',
];

$autoload = null;
foreach ($autoloadCandidates as $candidate) {
	if (is_file($candidate)) {
		$autoload = $candidate;
		break;
	}
}

if ($autoload === null) {
	fwrite(STDERR, "Composer autoload not found. Run composer install in the app or package.\n");
	exit(1);
}

require $autoload;

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');
defined('YII_ENV_TEST') or define('YII_ENV_TEST', true);
defined('YII_ENV_PROD') or define('YII_ENV_PROD', false);
defined('YII_ENV_DEV') or define('YII_ENV_DEV', false);

require_once dirname($autoload) . '/yiisoft/yii2/Yii.php';

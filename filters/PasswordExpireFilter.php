<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.4
 */

namespace cinghie\userextended\filters;

use Yii;
use cinghie\userextended\helpers\ModuleConfig;
use cinghie\userextended\models\User;
use yii\base\ActionFilter;
use yii\web\Response;

/**
 * Redirect users with an expired password to account settings.
 */
class PasswordExpireFilter extends ActionFilter
{
	/**
	 * Routes allowed while password is expired (module/controller/action).
	 *
	 * @var string[]
	 */
	public $allowedRoutes = [
		'user/settings/account',
		'user/security/logout',
		'user/security/login',
		'site/error',
		'site/captcha',
	];

	/**
	 * @inheritdoc
	 */
	public function beforeAction($action)
	{
		$days = (int) ModuleConfig::get('passwordMaxAgeDays', 0);
		if ($days <= 0) {
			return parent::beforeAction($action);
		}

		$userComponent = Yii::$app->user;
		if ($userComponent->getIsGuest()) {
			return parent::beforeAction($action);
		}

		$identity = $userComponent->identity;
		if (!$identity instanceof User || !$identity->isPasswordExpired()) {
			return parent::beforeAction($action);
		}

		$route = Yii::$app->requestedRoute ?: ($action->controller->uniqueId . '/' . $action->id);
		$route = ltrim((string) $route, '/');

		foreach ($this->allowedRoutes as $allowed) {
			if ($route === $allowed || strpos($route, $allowed . '/') === 0) {
				return parent::beforeAction($action);
			}
		}

		Yii::$app->session->setFlash(
			'warning',
			Yii::t('userextended', 'Your password has expired. Please set a new password.')
		);

		/** @var Response $response */
		$response = Yii::$app->getResponse();
		$response->redirect(['/user/settings/account']);
		Yii::$app->end();

		return false;
	}
}

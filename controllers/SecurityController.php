<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.1
 */

namespace cinghie\userextended\controllers;

use dektrium\user\controllers\SecurityController as BaseController;
use dektrium\user\models\LoginForm;
use yii\base\ExitException;
use yii\base\InvalidConfigException;

class SecurityController extends BaseController
{
	/**
	 * Displays the login page.
	 *
	 * @return string|Response
	 * @throws ExitException
	 * @throws InvalidConfigException
	 */
	public function actionLogin()
	{
		if (!\Yii::$app->user->isGuest) {
			$this->goHome();
		}

		/** @var LoginForm $model */
		$model = \Yii::createObject(LoginForm::className());
		$event = $this->getFormEvent($model);

		$this->performAjaxValidation($model);

		$this->trigger(self::EVENT_BEFORE_LOGIN, $event);

		\Yii::$app->session->setFlash('login', \Yii::t('userextended','Type your credentials'));

		if($model->load(\Yii::$app->getRequest()->post()))
		{
			if ($model->login()) {
				\Yii::$app->getSession()->setFlash('login', \Yii::t('userextended', 'Login successful'));
				$this->trigger(self::EVENT_AFTER_LOGIN, $event);
				return $this->goBack();
			}

			\Yii::$app->session->setFlash('login', \Yii::t('userextended', 'Incorrect Username or Password'));
		}

		return $this->render('login', [
			'model'  => $model,
			'module' => $this->module,
		]);
	}
}

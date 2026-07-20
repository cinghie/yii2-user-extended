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
use yii\base\Action;
use yii\base\ActionFilter;
use yii\web\NotFoundHttpException;

/**
 * Block public auth controllers on apps that must not expose them (CRM / backend).
 *
 * Default blocked controller IDs (Dektrium `user` module):
 * - `registration` → `/user/registration/*` (register, connect, confirm, resend)
 * - `recovery` → `/user/recovery/*` (request, reset)
 *
 * Unlike `dektrium\user\filters\BackendFilter`, profile and settings stay allowed
 * so backend admins can manage their account.
 *
 * Attach on the `user` module:
 *
 * ```php
 * 'user' => [
 *     'class' => \dektrium\user\Module::class,
 *     'as backend' => [
 *         'class' => \cinghie\userextended\filters\BackendFilter::class,
 *         // optional: 'controllers' => ['registration', 'recovery'],
 *     ],
 * ],
 * ```
 *
 * Or set `userextended.blockRegistrationAndRecovery = true` (Bootstrap attaches this filter).
 *
 * When public registration is required, do **not** attach this filter (or remove
 * `registration` from `$controllers`) and enable captcha/Turnstile, terms,
 * `enableConfirmation`, and registration rate limit instead.
 */
class BackendFilter extends ActionFilter
{
	/**
	 * Controller IDs under the `user` module that return 404.
	 *
	 * @var string[]
	 */
	public $controllers = ['recovery', 'registration'];

	/**
	 * @param Action $action
	 *
	 * @return bool
	 * @throws NotFoundHttpException
	 */
	public function beforeAction($action)
	{
		$controllerId = $action->controller->id;
		if (in_array($controllerId, $this->controllers, true)) {
			throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
		}

		return true;
	}
}

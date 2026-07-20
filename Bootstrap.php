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

namespace cinghie\userextended;

use Yii;
use cinghie\userextended\assets\SessionExpireAsset;
use cinghie\userextended\models\Account;
use cinghie\userextended\models\Assignment;
use cinghie\userextended\models\LoginForm;
use cinghie\userextended\models\Permission;
use cinghie\userextended\models\Profile;
use cinghie\userextended\models\RegistrationForm;
use cinghie\userextended\models\SettingsForm;
use cinghie\userextended\models\User;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Module as BaseModule;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\Application as WebApplication;
use yii\web\Cookie;
use yii\web\View;

/**
 * Bootstrap class
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * @var array
     */
    private $_modelMap = [
        'Account' => Account::class,
        'Assignment' => Assignment::class,
        'LoginForm' => LoginForm::class,
        'Permission' => Permission::class,
        'Profile' => Profile::class,
        'RegistrationForm' => RegistrationForm::class,
        'SettingsForm' => SettingsForm::class,
        'User' => User::class,
    ];

    /**
     * @param Application $app
     */
    public function bootstrap($app)
    {
        /**
         * @var Module $module
         * @var ActiveRecord $modelName
         */
        if ($app->hasModule('userextended') && ($module = $app->getModule('userextended')) instanceof BaseModule) {
            $this->_modelMap = array_merge($this->_modelMap, $module->modelMap);

            foreach ($this->_modelMap as $name => $definition) {
                $class = "cinghie\\userextended\\models\\" . $name;

                Yii::$container->set($class, $definition);
                $modelName = is_array($definition) ? $definition['class'] : $definition;
                $module->modelMap[$name] = $modelName;

                if (in_array($name, ['Account', 'Assignment', 'LoginForm', 'Permission', 'Profile', 'RegistrationForm', 'SettingsForm', 'User'], true)) {
                    Yii::$container->set($name . 'Query', function () use ($modelName) {
                        return $modelName::find();
                    });
                }
            }

            if ($app instanceof WebApplication) {
                $this->configureSessionExpire($app, $module);
            }
        }
    }

    /**
     * Configure session/auth timeout and client expire redirect.
     *
     * @param WebApplication $app
     * @param Module $module
     * @return void
     */
    protected function configureSessionExpire(WebApplication $app, Module $module): void
    {
        $timeout = (int) $module->sessionTimeout;
        if ($timeout < 0) {
            $timeout = 0;
        }

        if ($timeout > 0 && $app->has('session')) {
            $session = $app->getSession();
            $session->timeout = $timeout;

            // Do not force cookie lifetime here: overriding it can break session cookies
            // and produce corrupted HTML responses in some environments.
            $cookieParams = $session->getCookieParams();
            if (!isset($cookieParams['httponly'])) {
                $cookieParams['httponly'] = true;
            }
            if (!isset($cookieParams['sameSite'])) {
                $cookieParams['sameSite'] = Cookie::SAME_SITE_LAX;
            }
            $session->setCookieParams($cookieParams);
        }

        $app->on(Application::EVENT_BEFORE_REQUEST, function () use ($app, $module, $timeout) {
            if (!$app->has('user') || !$app->has('request')) {
                return;
            }

            $request = $app->getRequest();
            $user = $app->getUser();
            if ($module->disableAutoLogin) {
                $user->enableAutoLogin = false;
            }

            if ($timeout > 0) {
                $user->authTimeout = $timeout;
                if ($module->useAbsoluteAuthTimeout) {
                    $user->absoluteAuthTimeout = $timeout;
                }
            }

            // Client asset only on full HTML page loads for authenticated users
            if (
                $timeout <= 0
                || !$module->enableClientSessionExpireRedirect
                || $user->getIsGuest()
                || $request->getIsAjax()
            ) {
                return;
            }

            $accept = (string) $request->getHeaders()->get('Accept', '');
            if (
                $accept !== ''
                && stripos($accept, 'text/html') === false
                && stripos($accept, '*/*') === false
            ) {
                return;
            }

            $view = $app->getView();
            $view->on(View::EVENT_BEGIN_PAGE, function () use ($view, $module, $timeout) {
                static $registered = false;
                if ($registered) {
                    return;
                }
                $registered = true;

                SessionExpireAsset::register($view);

                $loginUrl = Url::to(['/user/security/login', 'expired' => 1]);
                $config = [
                    'timeout' => $timeout,
                    'warningBefore' => max(0, (int) $module->clientWarningBeforeExpire),
                    'warningMessage' => Yii::t('userextended', 'Your session is about to expire. Please save your work.'),
                    'loginUrl' => $loginUrl,
                    'loginPath' => '/user/security/login',
                ];

                $view->registerJs(
                    'window.userextendedSessionExpire = ' . Json::htmlEncode($config) . ';',
                    View::POS_HEAD
                );
            });
        });
    }
}

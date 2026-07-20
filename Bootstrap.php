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
use cinghie\userextended\components\WebUser;
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
use yii\web\User as YiiUser;
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
                $this->registerWebUser($app, $module);
                $this->configureSessionExpire($app, $module);
            }
        }
    }

    /**
     * Prefer WebUser so authTimeout can invalidate remember-me without cookie re-login.
     *
     * Must preserve Dektrium's container config (identityClass, loginUrl, …).
     * Only the DI definition is updated — do not replace the app `user` component
     * with a partial config (that caused "User::identityClass must be set").
     *
     * @param WebApplication $app
     * @param Module $module
     * @return void
     */
    protected function registerWebUser(WebApplication $app, Module $module): void
    {
        if (!$module->invalidateRememberMeOnAuthTimeout) {
            return;
        }

        $config = $this->resolveUserComponentConfig($app);
        if ($config === null) {
            return;
        }

        $config['class'] = WebUser::class;

        Yii::$container->set(YiiUser::class, $config);
        Yii::$container->set('yii\web\User', $config);
        Yii::$container->set(WebUser::class, $config);
    }

    /**
     * Build User component config from DI + user module modelMap.
     *
     * @param WebApplication $app
     * @return array|null null if identityClass cannot be resolved
     */
    protected function resolveUserComponentConfig(WebApplication $app): ?array
    {
        $config = [];

        $definitions = Yii::$container->getDefinitions();
        foreach ([YiiUser::class, 'yii\web\User', WebUser::class] as $id) {
            if (!isset($definitions[$id])) {
                continue;
            }
            $existing = $definitions[$id];
            if ($existing instanceof \Closure) {
                continue;
            }
            if (is_string($existing)) {
                $existing = ['class' => $existing];
            }
            if (is_array($existing)) {
                $config = array_merge($config, $existing);
                break;
            }
        }

        if (empty($config['identityClass'])) {
            $identityClass = $this->resolveIdentityClass($app);
            if ($identityClass === null) {
                return null;
            }
            $config['identityClass'] = $identityClass;
        }

        if (empty($config['loginUrl'])) {
            $config['loginUrl'] = ['/user/security/login'];
        }

        return $config;
    }

    /**
     * @param WebApplication $app
     * @return string|null
     */
    protected function resolveIdentityClass(WebApplication $app): ?string
    {
        if ($app->hasModule('user')) {
            $userModule = $app->getModule('user');
            if (is_object($userModule) && !empty($userModule->modelMap['User'])) {
                return (string) $userModule->modelMap['User'];
            }
        }

        return null;
    }

    /**
     * Ensure the application `user` component definition has identityClass before getUser().
     *
     * @param WebApplication $app
     * @return bool false when identityClass cannot be resolved
     */
    protected function ensureUserIdentityClass(WebApplication $app): bool
    {
        $identityClass = $this->resolveIdentityClass($app);

        $definitions = Yii::$container->getDefinitions();
        foreach ([YiiUser::class, 'yii\web\User', WebUser::class] as $id) {
            if (!isset($definitions[$id]) || !is_array($definitions[$id])) {
                continue;
            }
            if (!empty($definitions[$id]['identityClass'])) {
                $identityClass = $definitions[$id]['identityClass'];
                break;
            }
        }

        if ($identityClass === null || $identityClass === '') {
            return false;
        }

        // Keep DI definitions coherent (Dektrium + WebUser)
        foreach ([YiiUser::class, 'yii\web\User', WebUser::class] as $id) {
            $existing = isset($definitions[$id]) && is_array($definitions[$id]) ? $definitions[$id] : [];
            if (empty($existing['identityClass'])) {
                $existing['identityClass'] = $identityClass;
            }
            if (empty($existing['loginUrl'])) {
                $existing['loginUrl'] = ['/user/security/login'];
            }
            if ($id === WebUser::class || (!empty($existing['class']) && $existing['class'] === WebUser::class)) {
                $existing['class'] = WebUser::class;
            }
            Yii::$container->set($id, $existing);
        }

        $components = $app->getComponents(true);
        if (!isset($components['user'])) {
            return true;
        }

        $def = $components['user'];
        if (is_string($def)) {
            $def = ['class' => $def];
        }
        if (!is_array($def)) {
            return true;
        }

        if (empty($def['identityClass'])) {
            $def['identityClass'] = $identityClass;
            // Keep class as yii\web\User so Container can redirect to WebUser via DI
            if (empty($def['class'])) {
                $def['class'] = YiiUser::class;
            }
            $app->set('user', $def);
        }

        return true;
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

        if ($app->has('session')) {
            $session = $app->getSession();
            if ($timeout > 0) {
                $session->timeout = $timeout;
            }
            // Do not force cookie lifetime: overriding it can break session cookies
            // and produce corrupted HTML responses in some environments.
            if ($module->hardenSessionCookies) {
                $this->hardenSessionCookieParams($app, $module);
            }
        }

        $app->on(Application::EVENT_BEFORE_REQUEST, function () use ($app, $module, $timeout) {
            if (!$app->has('request')) {
                return;
            }

            // Always repair identityClass before touching the user component
            if (!$this->ensureUserIdentityClass($app)) {
                return;
            }

            if (!$app->has('user')) {
                return;
            }

            $request = $app->getRequest();
            $user = $app->getUser();

            // Guard: module id is also "user" — getUser() must return yii\web\User
            if (!$user instanceof YiiUser) {
                return;
            }

            if ($module->disableAutoLogin) {
                $user->enableAutoLogin = false;
            }

            if ($timeout > 0) {
                $user->authTimeout = $timeout;
            }

            $absolute = (int) $module->absoluteAuthTimeout;
            if ($absolute > 0) {
                $user->absoluteAuthTimeout = $absolute;
            } elseif ($module->useAbsoluteAuthTimeout && $timeout > 0) {
                $user->absoluteAuthTimeout = $timeout;
            }

            if ($module->hardenSessionCookies) {
                $this->hardenIdentityCookie($app, $module, $user);
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

    /**
     * @param WebApplication $app
     * @param Module $module
     * @return void
     */
    protected function hardenSessionCookieParams(WebApplication $app, Module $module): void
    {
        $session = $app->getSession();
        $cookieParams = $session->getCookieParams();

        if (!isset($cookieParams['httponly'])) {
            $cookieParams['httponly'] = true;
        }

        if (!array_key_exists('secure', $cookieParams) || $module->sessionCookieSecure !== null) {
            $cookieParams['secure'] = $this->resolveSecureFlag($app, $module);
        }

        if (!isset($cookieParams['sameSite'])) {
            $sameSite = $module->sessionSameSite;
            if ($sameSite === null || $sameSite === '') {
                $sameSite = Cookie::SAME_SITE_LAX;
            }
            $cookieParams['sameSite'] = $sameSite;
        }

        $session->setCookieParams($cookieParams);
    }

    /**
     * @param WebApplication $app
     * @param Module $module
     * @param YiiUser $user
     * @return void
     */
    protected function hardenIdentityCookie(WebApplication $app, Module $module, YiiUser $user): void
    {
        if (!$user->enableAutoLogin) {
            return;
        }

        $cookie = $user->identityCookie;
        if (!is_array($cookie)) {
            $cookie = ['name' => '_identity'];
        }

        if (!isset($cookie['httpOnly'])) {
            $cookie['httpOnly'] = true;
        }
        if (!array_key_exists('secure', $cookie) || $module->sessionCookieSecure !== null) {
            $cookie['secure'] = $this->resolveSecureFlag($app, $module);
        }
        if (!isset($cookie['sameSite'])) {
            $sameSite = $module->sessionSameSite;
            if ($sameSite === null || $sameSite === '') {
                $sameSite = Cookie::SAME_SITE_LAX;
            }
            $cookie['sameSite'] = $sameSite;
        }

        $user->identityCookie = $cookie;
    }

    /**
     * @param WebApplication $app
     * @param Module $module
     * @return bool
     */
    protected function resolveSecureFlag(WebApplication $app, Module $module): bool
    {
        if ($module->sessionCookieSecure !== null) {
            return (bool) $module->sessionCookieSecure;
        }

        if (defined('YII_ENV_PROD') && YII_ENV_PROD) {
            return true;
        }

        if ($app->has('request')) {
            return $app->getRequest()->getIsSecureConnection();
        }

        return false;
    }
}

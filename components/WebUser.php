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

namespace cinghie\userextended\components;

use Yii;
use yii\web\IdentityInterface;
use yii\web\User;

/**
 * WebUser with auth-timeout aware remember-me handling.
 *
 * Yii's default renewAuthStatus() can re-login from the request cookie after an
 * idle/absolute timeout even when removeIdentityCookie() was called (cookie is
 * still present on the current request). This class skips loginByCookie after
 * a timeout and clears the identity cookie in the response.
 */
class WebUser extends User
{
    /**
     * @inheritdoc
     */
    protected function renewAuthStatus()
    {
        $session = Yii::$app->getSession();
        $id = $session->getHasSessionId() || $session->getIsActive() ? $session->get($this->idParam) : null;

        if ($id === null) {
            $identity = null;
        } else {
            /** @var IdentityInterface $class */
            $class = $this->identityClass;
            $identity = $class::findIdentity($id);
            if ($identity === null) {
                $this->switchIdentity(null);
            }
        }

        if ($identity !== null) {
            $authKey = $session->get($this->authKeyParam);
            if ($authKey !== null && !$identity->validateAuthKey($authKey)) {
                $identity = null;
                $ip = Yii::$app->getRequest()->getUserIP();
                Yii::warning("Invalid session auth key attempted for user '$id' from $ip: $authKey", __METHOD__);
            }
        }

        $this->setIdentity($identity);

        $timedOut = false;
        if ($identity !== null && ($this->authTimeout !== null || $this->absoluteAuthTimeout !== null)) {
            $expire = $this->authTimeout !== null ? $session->get($this->authTimeoutParam) : null;
            $expireAbsolute = $this->absoluteAuthTimeout !== null ? $session->get($this->absoluteAuthTimeoutParam) : null;
            if (($expire !== null && $expire < time()) || ($expireAbsolute !== null && $expireAbsolute < time())) {
                $timedOut = true;
                $this->logout(false);
            } elseif ($this->authTimeout !== null) {
                $session->set($this->authTimeoutParam, time() + $this->authTimeout);
            }
        }

        if ($this->enableAutoLogin) {
            if ($this->getIsGuest()) {
                if ($timedOut) {
                    $this->removeIdentityCookie();
                } else {
                    $this->loginByCookie();
                }
            } elseif ($this->autoRenewCookie) {
                $this->renewIdentityCookie();
            }
        }
    }
}

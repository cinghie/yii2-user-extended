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
use cinghie\userextended\helpers\SecurityAudit;
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
                Yii::warning("Invalid session auth key attempted for user '$id' from $ip", __METHOD__);
                SecurityAudit::log('session_authkey_invalid', (int) $id, [], 'session', 'User', '/');
            }
        }

        $this->setIdentity($identity);

        $timedOut = false;
        if ($identity !== null && ($this->authTimeout !== null || $this->absoluteAuthTimeout !== null)) {
            $expire = $this->authTimeout !== null ? $session->get($this->authTimeoutParam) : null;
            $expireAbsolute = $this->absoluteAuthTimeout !== null ? $session->get($this->absoluteAuthTimeoutParam) : null;
            $idleExpired = $expire !== null && $expire < time();
            $absoluteExpired = $expireAbsolute !== null && $expireAbsolute < time();
            if ($idleExpired || $absoluteExpired) {
                $timedOut = true;
                $userId = (int) $identity->getId();
                $this->logout(false);
                SecurityAudit::log('session_expire', $userId, [
                    'reason' => $absoluteExpired ? 'absolute' : 'idle',
                ], 'session', 'User', '/');
            } elseif ($this->authTimeout !== null) {
                // Dev tools (debug/gii) poll via AJAX and would otherwise keep idle forever.
                // Still initialize __expire on first hit; only skip renewals for background probes.
                if ($expire === null || $this->shouldRenewIdleAuthTimeout()) {
                    $session->set($this->authTimeoutParam, time() + $this->authTimeout);
                }
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

    /**
     * Whether this request should extend the idle authTimeout window.
     *
     * Yii debug toolbar / Gii poll continuously; treating them as activity prevents
     * automatic logout in local/Docker (YII_DEBUG) environments.
     *
     * @return bool
     */
    protected function shouldRenewIdleAuthTimeout(): bool
    {
        if (!Yii::$app->has('request')) {
            return true;
        }

        $path = trim((string) Yii::$app->getRequest()->getPathInfo(), '/');
        if ($path === '') {
            return true;
        }

        // Optional language prefix (e.g. it/debug/...) when not stripped by urlManager
        if (preg_match('#^(?:[a-z]{2}(?:-[a-z]{2})?/)?(?:debug|gii)(?:/|$)#i', $path)) {
            return false;
        }

        return true;
    }
}

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
use dektrium\user\Module as BaseUser;
use yii\i18n\PhpMessageSource;

/**
 * Class Module
 */
class Module extends BaseUser
{
    /**
     * @var string Module version
     */
    private $version = '0.6.4';

    /**
     * Session/auth idle timeout in seconds.
     * 0 disables module-driven session expire handling.
     *
     * @var int
     */
    public $sessionTimeout = 3600;

    /**
     * If true and absoluteAuthTimeout is 0, also set user.absoluteAuthTimeout to sessionTimeout.
     *
     * @var bool
     */
    public $useAbsoluteAuthTimeout = false;

    /**
     * Absolute max login duration in seconds (independent of idle activity).
     * 0 = disabled unless useAbsoluteAuthTimeout is true (then uses sessionTimeout).
     *
     * @var int
     */
    public $absoluteAuthTimeout = 0;

    /**
     * If true, register client-side redirect to login when the session expires.
     *
     * @var bool
     */
    public $enableClientSessionExpireRedirect = true;

    /**
     * Seconds before expire when a browser warning can be shown.
     * 0 disables the warning.
     *
     * @var int
     */
    public $clientWarningBeforeExpire = 60;

    /**
     * If true, disables Yii auto-login (remember-me) so authTimeout works and
     * an expired session always requires credentials again. Recommended for CRM.
     *
     * @var bool
     */
    public $disableAutoLogin = true;

    /**
     * Apply Secure / HttpOnly / SameSite on the session cookie when missing.
     *
     * @var bool
     */
    public $hardenSessionCookies = true;

    /**
     * Session cookie Secure flag: null = auto (true on HTTPS or YII_ENV_PROD), true/false = force.
     *
     * @var bool|null
     */
    public $sessionCookieSecure = null;

    /**
     * Session cookie SameSite when not already set (Lax|Strict|None). null = Lax.
     *
     * @var string|null
     */
    public $sessionSameSite = null;

    /**
     * Regenerate session ID after successful login and on logout (defense in depth;
     * Yii User::switchIdentity already regenerates).
     *
     * @var bool
     */
    public $regenerateSessionId = true;

    /**
     * After idle/absolute auth timeout, clear remember-me and do not re-login from cookie.
     * Requires components\WebUser (registered by Bootstrap).
     *
     * @var bool
     */
    public $invalidateRememberMeOnAuthTimeout = true;

    /**
     * @var string Path to avatar file
     */
    public $avatarPath = '@webroot/img/users/';

    /**
     * @var string URL to avatar file
     */
    public $avatarURL  = '@web/img/users/';

    /**
     * Allowed avatar file extensions (lowercase, without dot).
     *
     * @var string[]
     */
    public $avatarAllowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Max avatar upload size in bytes (default 2MB).
     *
     * @var int
     */
    public $avatarMaxSize = 2097152;

    /**
     * Cache RBAC role name list used by admin filters.
     *
     * @var bool
     */
    public $enableRbacRoleCache = true;

    /**
     * TTL (seconds) for RBAC role name cache.
     *
     * @var int
     */
    public $rbacRoleCacheDuration = 3600;

    /**
     * Enable login brute-force protection (IP + username/email).
     *
     * @var bool
     */
    public $enableLoginRateLimit = true;

    /**
     * Failed attempts before temporary lock.
     *
     * @var int
     */
    public $loginMaxAttempts = 5;

    /**
     * Window (seconds) for counting failed attempts in cache TTL.
     *
     * @var int
     */
    public $loginAttemptWindow = 900;

    /**
     * Lock duration (seconds) after max attempts.
     *
     * @var int
     */
    public $loginLockoutDuration = 900;

    /**
     * Sleep after failed login based on attempt count.
     *
     * @var bool
     */
    public $loginProgressiveDelay = true;

    /**
     * Base delay seconds × attempt count (capped by loginDelayMaxSeconds).
     *
     * @var int
     */
    public $loginDelayBaseSeconds = 1;

    /**
     * Max progressive delay in seconds.
     *
     * @var int
     */
    public $loginDelayMaxSeconds = 5;

    /**
     * Show captcha after N failed attempts (0 disables).
     *
     * @var int
     */
    public $loginCaptchaAfterAttempts = 3;

    /**
     * Captcha action route for login form.
     *
     * @var string|array
     */
    public $loginCaptchaAction = ['/site/captcha'];

    /**
     * Enable Cloudflare Turnstile on login.
     *
     * @var bool
     */
    public $enableCloudflareTurnstile = false;

    /**
     * Cloudflare Turnstile site key (public).
     *
     * @var string
     */
    public $cloudflareSiteKey = '';

    /**
     * Cloudflare Turnstile secret key (private — set only in web-local).
     *
     * @var string
     */
    public $cloudflareSecretKey = '';

    /**
     * Turnstile widget theme: auto|light|dark.
     *
     * @var string
     */
    public $cloudflareTurnstileTheme = 'auto';

    /**
     * Also require Turnstile on registration when enableCloudflareTurnstile is true.
     *
     * @var bool
     */
    public $cloudflareTurnstileOnRegistration = false;

	/**
	 * @var string default User Role
	 */
	public $defaultRole = '';

	/**
	 * @var boolean avatar
	 */
	public $account = false;

	/**
	 * @var boolean avatar
	 */
	public $avatar = true;

	/**
	 * @var boolean bio
	 */
	public $bio = false;

	/**
	 * @var boolean birthday
	 */
	public $birthday = true;

	/**
	 * @var boolean bio
	 */
	public $contact = false;

	/**
	 * @var boolean firstname
	 */
	public $firstname = true;

	/**
	 * @var boolean lastname
	 */
	public $lastname = true;

	/**
	 * @var boolean captcha
	 */
	public $captcha = true;

	/**
	 * @var boolean gravatar
	 */
	public $gravatarEmail = false;

	/**
	 * @var boolean location
	 */
	public $location = false;

	/**
	 * @var boolean onlyEmail
	 */
	public $onlyEmail = false;

	/**
	 * @var boolean publicEmail
	 */
	public $publicEmail = false;

	/**
	 * @var boolean signature
	 */
	public $signature = true;

	/**
	 * Allow limited HTML in signature (Imperavi/CKEditor). If false, store/display as plain text.
	 *
	 * @var bool
	 */
	public $signatureAllowHtml = false;

	/**
	 * HtmlPurifier HTML.Allowed when signatureAllowHtml is true.
	 *
	 * @var string
	 */
	public $signatureAllowedHtml = 'p,br,strong,b,em,i,ul,ol,li,a[href|title|target|rel],span';

	/**
	 * @var boolean terms
	 */
	public $terms = true;

	/**
	 * @var boolean website
	 */
	public $website = false;

	/**
	 * @var string login template
	 */
	public $templateLogin = 'login';

	/**
	 * @var string logo url
	 */
	public $templateLogoURL = '@web/logo.png';

	/**
	 * @var string register template
	 */
	public $templateRegister = '_two_column';

	/**
     * @var boolean showAlert in views
     */
    public $showAlert = true;

    /**
     * @var boolean showTitles in views
     */
    public $showTitles = true;

    /**
     * @var boolean socialLogin
     */
    public $socialLogin = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Translate
        $this->registerTranslations();
    }

    /**
     * Get module version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Translating module message
     */
    public function registerTranslations()
    {
        if (empty(Yii::$app->i18n->translations['userextended']))
        {
            Yii::$app->i18n->translations['userextended'] = [
                'class' => PhpMessageSource::class,
                'basePath' => __DIR__ . '/messages'
            ];
        }
    }
}

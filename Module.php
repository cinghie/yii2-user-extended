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
use cinghie\userextended\helpers\ModuleSettings;
use dektrium\user\Module as BaseUser;
use yii\base\InvalidConfigException;
use yii\i18n\PhpMessageSource;

/**
 * Class Module
 */
class Module extends BaseUser
{
    /**
     * Soft-apply recommended security defaults: `null` (off), `auto`, `dev`, or `prod`.
     * `auto` → `prod` when `YII_ENV_PROD`, otherwise `dev`.
     * Only properties still at factory defaults are overwritten (explicit config wins).
     * Prefer `array_merge(ModuleSettings::securityPreset('prod'), [...])` in app config for full control.
     *
     * @var string|null
     */
    public $securityPreset = null;

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
     * Show the expire warning at most once per idle cycle (until activity renews the timer).
     *
     * @var bool
     */
    public $clientWarningOnce = true;

    /**
     * Optional client heartbeat interval in seconds to align with server authTimeout.
     * 0 disables heartbeat. Uses an AJAX GET with X-Requested-With so assets are not registered.
     * Note: while heartbeat succeeds, the client timer (and server authTimeout) keep renewing.
     *
     * @var int
     */
    public $clientSessionHeartbeatInterval = 0;

    /**
     * Heartbeat URL. When null and heartbeat interval > 0, defaults to `/user/security/session-ping`
     * (204 keep-alive). Do not point this at a full HTML page.
     *
     * @var string|null
     */
    public $clientSessionHeartbeatUrl = null;

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
     * Block adding new roles/permissions to your own account (self-escalation).
     *
     * @var bool
     */
    public $blockSelfRoleAssignment = true;

    /**
     * Audit RBAC assignment changes (also requires enableSecurityAudit).
     *
     * @var bool
     */
    public $enableRbacAssignmentAudit = true;

    /**
     * Structured security audit (login, block/delete, Turnstile, session expire, RBAC).
     * Never logs passwords or tokens. Uses cinghie logger when available, else Yii::info.
     *
     * @var bool
     */
    public $enableSecurityAudit = true;

    /**
     * Enable login brute-force protection (IP + username/email).
     *
     * @var bool
     */
    public $enableLoginRateLimit = true;

    /**
     * Where to store login/registration rate-limit counters.
     * `db` (default) uses {{%userextended_rate_limit}} so lockouts survive cache flush;
     * `cache` uses Yii cache (session fallback); `auto` prefers DB when the table exists.
     *
     * @var string
     */
    public $rateLimitStorage = 'db';

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
     * Attach BackendFilter to the `user` module (404 on registration + recovery).
     * Prefer this OR explicit `'as backend' => BackendFilter::class` on `user` (not both).
     * CRM/backend: true when public signup/recovery must stay unavailable.
     *
     * @var bool
     */
    public $blockRegistrationAndRecovery = false;

    /**
     * Throttle public registration / confirmation resend (IP + email).
     *
     * @var bool
     */
    public $enableRegistrationRateLimit = true;

    /**
     * @var int
     */
    public $registrationMaxAttempts = 5;

    /**
     * @var int
     */
    public $registrationAttemptWindow = 900;

    /**
     * @var int
     */
    public $registrationLockoutDuration = 900;

    /**
     * @var bool
     */
    public $registrationProgressiveDelay = true;

    /**
     * @var int
     */
    public $registrationDelayBaseSeconds = 1;

    /**
     * @var int
     */
    public $registrationDelayMaxSeconds = 5;

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
     * Enable configurable password complexity policy.
     *
     * @var bool
     */
    public $enablePasswordPolicy = true;

    /**
     * @var int
     */
    public $passwordMinLength = 8;

    /**
     * Max length (capped at 72 for bcrypt).
     *
     * @var int
     */
    public $passwordMaxLength = 72;

    /**
     * @var bool
     */
    public $passwordRequireUppercase = true;

    /**
     * @var bool
     */
    public $passwordRequireLowercase = true;

    /**
     * @var bool
     */
    public $passwordRequireDigit = true;

    /**
     * @var bool
     */
    public $passwordRequireSpecial = false;

    /**
     * Reject passwords from the built-in + passwordCommonList lists.
     *
     * @var bool
     */
    public $passwordBanCommon = true;

    /**
     * Extra common passwords to ban (lowercase recommended).
     *
     * @var string[]
     */
    public $passwordCommonList = [];

    /**
     * Force password change after N days (0 = disabled). Uses user.password_changed_at.
     *
     * @var int
     */
    public $passwordMaxAgeDays = 0;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        ModuleSettings::applySecurityPreset($this);
        ModuleSettings::validate($this);
        $this->registerTranslations();
    }

    /**
     * Recommended security defaults for app config merge.
     *
     * @param string $name `dev`|`prod`|`auto`
     *
     * @return array<string, mixed>
     * @throws InvalidConfigException
     */
    public static function securityPreset(string $name): array
    {
        return ModuleSettings::securityPreset($name);
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
        if (!isset(Yii::$app->i18n->translations['userextended'])) {
            Yii::$app->i18n->translations['userextended'] = [
                'class' => PhpMessageSource::class,
                'basePath' => __DIR__ . '/messages',
                'sourceLanguage' => 'en',
            ];
        }
    }
}

# Yii2 User Extended

![License](https://img.shields.io/packagist/l/cinghie/yii2-user-extended.svg)
![Latest Stable Version](https://img.shields.io/github/release/cinghie/yii2-user-extended.svg)
![Latest Release Date](https://img.shields.io/github/release-date/cinghie/yii2-user-extended.svg)
![Latest Commit](https://img.shields.io/github/last-commit/cinghie/yii2-user-extended.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/cinghie/yii2-user-extended.svg)](https://packagist.org/packages/cinghie/yii2-user-extended)

Yii2 User Extended to extend Yii2 User by Dektrium: https://github.com/dektrium/yii2-user

This is not an standalone module to manage users but a module to extend Yii2 User extension.

Current package version: **0.6.4** (see `CHANGELOG.md`).

Installation
-----------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require cinghie/yii2-user-extended "*"
```

or add this line to the require section of your `composer.json` file.

```
"cinghie/yii2-user-extended": "*"
```

Configuration
-----------------

### 1. Images folder

Copy img folder to your webroot

### 2. Update yii2 user database schema

Make sure that you have properly configured `db` application component
and run the following command:
```
$ php yii migrate/up --migrationPath=@vendor/dektrium/yii2-user/migrations
```

### 3. Add Yii2 RBAC migrations 

Add to common config file

```
'components' => [
    'authManager' => [
        'class' => 'yii\rbac\DbManager',
    ],
]

'modules' => [
    'rbac' => 'dektrium\rbac\RbacWebModule',
],
```
and run migration

```
$ php yii migrate/up --migrationPath=@yii/rbac/migrations
```

### 4. Update yii2 user extended database schema

```
$ php yii migrate/up --migrationPath=@vendor/cinghie/yii2-user-extended/migrations
```

If the app already lists that path in `console` `controllerMap.migrate.migrationPath` (as CorimaCRM does), plain `php yii migrate` is enough.

This applies (among others):

- profile / user extended schema updates
- admin grid indexes (e.g. `user.last_login_at`)
- `user.password_changed_at` (password rotation / `passwordMaxAgeDays`)
- **`userextended_rate_limit`** — DB-backed login/registration lockout (default `rateLimitStorage=db`). **Required on every environment** after upgrading; without it the module falls back to cache and logs a warning.

### 5. Set configuration file

Set on your configuration file, in modules section

```
'modules' =>  [
    // Yii2 RBAC
    'rbac' => [
        'class' => 'dektrium\rbac\RbacWebModule',
        // Invalidate cached role list after role CRUD
        'controllerMap' => [
            'role' => 'cinghie\userextended\controllers\RoleController',
            'permission' => 'cinghie\userextended\controllers\PermissionController',
        ],
    ],
    // Yii2 User
    'user' => [
        'class' => 'dektrium\user\Module',
        // Disable user impersonation (recommended)
        'enableImpersonateUser' => false,
        // Yii2 User Controllers Overrides
        'controllerMap' => [
            'admin' => 'cinghie\userextended\controllers\AdminController',
            'security' => 'cinghie\userextended\controllers\SecurityController',
            'settings' => 'cinghie\userextended\controllers\SettingsController',
        ],
        // Yii2 User Models Overrides
        'modelMap' => [
            'LoginForm' => 'cinghie\userextended\models\LoginForm',
            'RecoveryForm' => 'cinghie\userextended\models\RecoveryForm',
            'RegistrationForm' => 'cinghie\userextended\models\RegistrationForm',
            'Profile' => 'cinghie\userextended\models\Profile',
            'SettingsForm' => 'cinghie\userextended\models\SettingsForm',
            'User' => 'cinghie\userextended\models\User',
        ],
    ],
    // Yii2 User Extended
    'userextended' => [
        'class' => 'cinghie\userextended\Module',

        // Paths
        'avatarPath' => '@webroot/img/users/',
        'avatarURL' => '@web/img/users/',

        // Profile fields
        'defaultRole' => '', // example 'registered'
        'account' => false,
        'avatar' => true,
        'bio' => false,
        'captcha' => true,
        'birthday' => true,
        'contact' => false,
        'firstname' => true,
        'gravatarEmail' => false,
        'lastname' => true,
        'location' => false,
        'onlyEmail' => false,
        'publicEmail' => false,
        'signature' => true,
        'terms' => true,
        'website' => false,

        // Templates / UI
        'templateLogin' => 'login_prestashop', // login or login_prestashop
        'templateLogoURL' => '@web/logo.png',
        'templateRegister' => '_two_column', // _one_column or _two_column
        'showAlert' => true,
        'showTitles' => true, // Set false in adminLTE
        'socialLogin' => false,

        // Avatar upload hardening
        'avatarAllowedExtensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'avatarMaxSize' => 2097152, // 2MB

        // XSS / signature
        'signatureAllowHtml' => false, // plain text by default; true = HtmlPurifier whitelist
        'signatureAllowedHtml' => 'p,br,strong,b,em,i,ul,ol,li,a[href|title|target|rel],span',

        // Password policy
        'enablePasswordPolicy' => true,
        'passwordMinLength' => 8,
        'passwordMaxLength' => 72,
        'passwordRequireUppercase' => true,
        'passwordRequireLowercase' => true,
        'passwordRequireDigit' => true,
        'passwordRequireSpecial' => false,
        'passwordBanCommon' => true,
        'passwordCommonList' => [], // extra banned passwords
        'passwordMaxAgeDays' => 0, // 0 = disabled; e.g. 90 forces periodic change
        'passwordHashCost' => 13, // bcrypt 12–15 (default 13); old hashes still verify
        'rehashPasswordOnLogin' => true, // upgrade-only rehash after successful login

        // Session expire / auth hardening
        'sessionTimeout' => 3600, // seconds; 0 disables module session handling
        'useAbsoluteAuthTimeout' => false, // if true and absoluteAuthTimeout=0, use sessionTimeout
        'absoluteAuthTimeout' => 0, // max login duration in seconds (0 = off unless useAbsoluteAuthTimeout)
        'enableClientSessionExpireRedirect' => true,
        'clientWarningBeforeExpire' => 60, // 0 disables warning
        'clientWarningOnce' => true,
        'clientSessionHeartbeatInterval' => 0, // e.g. 300 to ping every 5 minutes
        'clientSessionHeartbeatUrl' => null, // null = current page
        'disableAutoLogin' => true, // CRM recommended: no remember-me
        'hardenSessionCookies' => true,
        'sessionCookieSecure' => null, // null = auto (HTTPS or prod), true/false = force
        'sessionSameSite' => null, // null = Lax when not already set
        'regenerateSessionId' => true,
        'invalidateRememberMeOnAuthTimeout' => true,

        // Login rate limit / brute-force
        'enableLoginRateLimit' => true,
        'rateLimitStorage' => 'db', // db | cache | auto — db survives cache flush
        'loginMaxAttempts' => 5,
        'loginAttemptWindow' => 900,
        'loginLockoutDuration' => 900,
        'loginProgressiveDelay' => true,
        'loginDelayBaseSeconds' => 1,
        'loginDelayMaxSeconds' => 5,
        'loginCaptchaAfterAttempts' => 3, // 0 disables
        'loginCaptchaAction' => ['/site/captcha'],

        // RBAC role list cache (admin filters)
        'enableRbacRoleCache' => true,
        'rbacRoleCacheDuration' => 3600,

        // Cloudflare Turnstile (optional; keep secret in web-local / env)
        'enableCloudflareTurnstile' => false,
        'cloudflareSiteKey' => '',
        'cloudflareSecretKey' => '', // never commit real secrets
        'cloudflareTurnstileTheme' => 'auto', // auto|light|dark
        'cloudflareTurnstileOnRegistration' => false,
    ],
]
```

Put secrets and environment-specific overrides in `web-local.php` (example):

```
'modules' => [
    'userextended' => [
        'enableCloudflareTurnstile' => true,
        'cloudflareSiteKey' => 'your-site-key',
        'cloudflareSecretKey' => 'your-secret-key',
        'cloudflareTurnstileTheme' => 'auto',
        'cloudflareTurnstileOnRegistration' => false,
        // 'sessionTimeout' => 3600,
        // 'disableAutoLogin' => true,
    ],
],
```

and in components section

```
'components' =>  [
    'cache' => [
        'class' => 'yii\caching\FileCache', // required for rate limit + RBAC role cache
    ],
    'view' => [
        'theme' => [
            'pathMap' => [
                '@dektrium/rbac/views/permission' => '@vendor/cinghie/yii2-user-extended/views/permission',  
                '@dektrium/rbac/views/role' => '@vendor/cinghie/yii2-user-extended/views/role',  
                '@dektrium/rbac/views/rule' => '@vendor/cinghie/yii2-user-extended/views/rule',  
                '@dektrium/user/views/admin' => '@vendor/cinghie/yii2-user-extended/views/admin',  
                '@dektrium/user/views/profile' => '@vendor/cinghie/yii2-user-extended/views/profile',  
                '@dektrium/user/views/role' => '@vendor/cinghie/yii2-user-extended/views/role',  
                '@dektrium/user/views/security' => '@vendor/cinghie/yii2-user-extended/views/adminlte/security',  
                '@dektrium/user/views/settings' => '@vendor/cinghie/yii2-user-extended/views/settings',  
            ],
        ],
    ],
]
```

If you have a Yii2 App Advanced **or a CRM that must not expose public signup/password recovery**, attach the userextended filter on the `user` module:

```
'modules' =>  [

    'user' => [
        'class' => 'dektrium\user\Module',
        // 404 on /user/registration/* and /user/recovery/*
        // (unlike dektrium BackendFilter, profile + settings stay available)
        'as backend' => [
            'class' => 'cinghie\userextended\filters\BackendFilter',
            // 'controllers' => ['registration', 'recovery'],
        ],
        'enableRegistration' => false,
        'enablePasswordRecovery' => false,
        'enableImpersonateUser' => false,
        'controllerMap' => [
            'registration' => 'cinghie\userextended\controllers\RegistrationController',
        ],
    ],

],
```

Alternative: set `userextended.blockRegistrationAndRecovery = true` (Bootstrap attaches the same filter if `as backend` is not already set).

Dektrium’s own filter also blocks `profile` and `settings` — prefer userextended on backend/CRM:

```
'as backend' => 'dektrium\user\filters\BackendFilter',
```

### 6. Set captcha in Controller

Required when registration captcha is enabled and/or login rate-limit captcha (`loginCaptchaAfterAttempts`) is used.

In your `SiteController` set in `actions()`:

```
'captcha' => [
    'class' => 'yii\captcha\CaptchaAction',
    'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
    'minLength' => 6,
    'maxLength' => 6,
],
```

Allow guests to reach the captcha action in `AccessControl` (`actions` => `['error', 'captcha']`).

Module parameters (0.6.4)
-----------------

Parameters are validated in `Module::init()` (ranges clamped; invalid Turnstile/SameSite/password lengths throw `InvalidConfigException`).

### Security presets (`dev` / `prod`)

| Option | Description |
|--------|-------------|
| `securityPreset` | `null` (off), `auto`, `dev`, or `prod`. Soft-applies recommended defaults only where values are still at factory defaults (explicit config wins). `auto` → `prod` when `YII_ENV_PROD`. |

```php
use cinghie\userextended\Module;

'userextended' => array_merge(Module::securityPreset('prod'), [
    'class' => Module::class,
    // overrides / secrets
    'cloudflareSiteKey' => '...',
    'cloudflareSecretKey' => '...',
]),
```

Or set `'securityPreset' => 'auto'` on the module and override only what you need.

**prod** highlights: `sessionTimeout=1800`, `useAbsoluteAuthTimeout=true`, rate limits + progressive delay on, captcha after 3 failures, password policy on, self-role block + audit on.  
**dev** highlights: `sessionTimeout=7200`, no progressive delay / login captcha-after-attempts, Turnstile off, still keeps rate limit + password policy + audit.

### Session expire

| Parameter | Default | Description |
|-----------|---------|-------------|
| `sessionTimeout` | `3600` | Idle session/auth timeout in seconds. `0` disables module session handling. |
| `useAbsoluteAuthTimeout` | `false` | If `absoluteAuthTimeout` is `0`, also set `user.absoluteAuthTimeout` to `sessionTimeout`. |
| `absoluteAuthTimeout` | `0` | Max login duration in seconds (`0` = off unless `useAbsoluteAuthTimeout`). |
| `enableClientSessionExpireRedirect` | `true` | Client JS redirects to login with `?expired=1`. |
| `clientWarningBeforeExpire` | `60` | Warning seconds before expire (`0` = off). Capped below `sessionTimeout`. |
| `clientWarningOnce` | `true` | Show toast at most once per idle cycle. |
| `clientSessionHeartbeatInterval` | `0` | Optional heartbeat seconds (`0` = off). While it succeeds, client + server idle timers renew. |
| `clientSessionHeartbeatUrl` | `null` | Heartbeat URL; when null and interval > 0, uses `/user/security/session-ping` (204). |
| `disableAutoLogin` | `true` | Disable remember-me so idle `authTimeout` works (CRM recommended). |
| `hardenSessionCookies` | `true` | Apply HttpOnly / Secure / SameSite on session (and identity) cookies when missing. |
| `sessionCookieSecure` | `null` | `null` = auto (HTTPS or prod); `true`/`false` force Secure. |
| `sessionSameSite` | `null` | SameSite when unset (`null` → `Lax`). Must be `Lax`/`Strict`/`None` if set. |
| `regenerateSessionId` | `true` | Extra session ID regenerate after login (logout uses Yii `logout(true)`). |
| `invalidateRememberMeOnAuthTimeout` | `true` | Use `WebUser` so timeout clears remember-me without cookie re-login. |

### Profile / registration UI flags

| Parameter | Default | Description |
|-----------|---------|-------------|
| `avatar` | `true` | Enable avatar field / upload. |
| `avatarPath` | `@webroot/img/users/` | Storage path alias. |
| `avatarURL` | `@web/img/users/` | Public URL alias. |
| `firstname` / `lastname` / `birthday` / `signature` | `true` | Profile fields. |
| `bio` / `contact` / `account` / `location` / `website` / `publicEmail` / `gravatarEmail` | `false` | Optional profile fields. |
| `captcha` | `true` | Yii captcha on registration form. |
| `terms` | `true` | Terms checkbox on registration. |
| `defaultRole` | `''` | Role name assigned after register (empty = none). |
| `onlyEmail` | `false` | Registration email-only mode (Dektrium). |
| `templateLogin` | `login` | Login view name (`login` / `login_prestashop`). |
| `templateRegister` | `_two_column` | Register layout partial. |
| `templateLogoURL` | `@web/logo.png` | Logo for Prestashop-style login. |
| `showAlert` / `showTitles` | `true` | View chrome flags. |
| `socialLogin` | `false` | Social auth UI hooks. |
| `blockRegistrationAndRecovery` | `false` | Bootstrap attaches `BackendFilter` on `user` if missing. |

### i18n

Category `userextended` (`messages/en`, `messages/it`, `sourceLanguage=en`). Covers session expire, login/registration security, password policy, avatar errors, and admin UI labels.

### Assets

`SessionExpireAsset` publishes from `assets/static/` (`realpath`, not `@vendor/...`). PHP asset classes stay outside `sourcePath`, so they are not web-exposed even when `assetManager.linkAssets` is enabled. In production (`YII_ENV_PROD` or `!YII_DEBUG`) it loads `session-expire.min.js` / `.min.css`; URLs append `?v=<mtime>` for cache busting. Debug sets `forceCopy` so local edits appear without flushing `web/assets`. Optional app-wide: `assetManager.appendTimestamp = true`.

### Tests

From the package directory (with the host app’s Composer autoload):

```bash
vendor/bin/phpunit -c tests/phpunit.xml
```

Covers login rate limit lock/clear, **DB-backed lockout survives cache flush** (`DbRateLimitStoreTest`), UserSearch role SQL-injection rejection, avatar upload validation rejects, admin `switch` forbidden, session `?expired=1` smoke (via `SecurityController::actionLogin`), Turnstile missing/invalid/valid (mock `siteVerifyHandler`), password policy, `ModuleSettings` validation/presets, i18n-safe login lock counting, and **Yii2 best-practice checks** (`Yii2BestPracticesTest`: AccessControl/VerbFilter/CSRF, AssetBundle `sourcePath` + `appendTimestamp`, Assignment `safeAttributes`, BootstrapInterface, parameterized UserSearch SQL, SafeHtml encoding).

### CSRF / HTTP verbs

Mutating admin actions (`block`, `confirm`, `delete`, bulk activate/deactivate/delete, `resend-password`) and RBAC role/permission `delete` require **POST**. CSRF is enforced by the controller (`enableCsrfValidation`, default on); ActiveForm emits the token field automatically. Bulk user AJAX posts include the CSRF token explicitly.

### XSS output

| Parameter | Default | Description |
|-----------|---------|-------------|
| `signatureAllowHtml` | `false` | If `false`, signature is stored/shown as plain text. If `true`, HtmlPurifier whitelist applies. |
| `signatureAllowedHtml` | `p,br,strong,...` | HtmlPurifier `HTML.Allowed` when HTML is enabled. |

Bio and name fields are always stripped to plain text on save. Views encode usernames, roles, and attributes; use `Profile::getBioHtml()` / `getSignatureHtml()` for display.

### Password policy

| Parameter | Default | Description |
|-----------|---------|-------------|
| `enablePasswordPolicy` | `true` | Enforce complexity rules on new passwords. |
| `passwordMinLength` | `8` | Minimum length (`>= 1`, `<= passwordMaxLength`). |
| `passwordMaxLength` | `72` | Maximum length (clamped to bcrypt limit 72). |
| `passwordRequireUppercase` | `true` | Require A–Z. |
| `passwordRequireLowercase` | `true` | Require a–z. |
| `passwordRequireDigit` | `true` | Require 0–9. |
| `passwordRequireSpecial` | `false` | Require non-alphanumeric. |
| `passwordBanCommon` | `true` | Reject common passwords. |
| `passwordCommonList` | `[]` | Extra banned passwords. |
| `passwordMaxAgeDays` | `0` | Force change after N days (`0` = off). Requires migration `password_changed_at`. |
| `passwordHashCost` | `13` | bcrypt cost for **new** hashes (via Dektrium `user.cost`). Clamped to **12–15** (never weaker than 12). Existing hashes keep working (`password_verify`). Bootstrap only **raises** `user.cost`, never lowers a higher value. |
| `rehashPasswordOnLogin` | `true` | After successful login, upgrade `password_hash` if its cost is below `passwordHashCost`. Re-validates the password before rewrite; refuses to save a weaker hash; does not change `password_changed_at`. |

Hashing and verification use only `dektrium\user\helpers\Password` (Yii `security`). Map `RecoveryForm` to `cinghie\userextended\models\RecoveryForm`.

### Avatar upload security

| Parameter | Default | Description |
|-----------|---------|-------------|
| `avatarAllowedExtensions` | `jpg,jpeg,png,webp` | Allowed extensions (required non-empty). |
| `avatarMaxSize` | `2097152` | Max upload size in bytes (2MB, `>= 1`). |

Uploads are renamed randomly, MIME/`getimagesize` checked, double extensions blocked, path confined under `avatarPath`.

### Login rate limit

| Parameter | Default | Description |
|-----------|---------|-------------|
| `enableLoginRateLimit` | `true` | Enable IP + username/email counters. |
| `rateLimitStorage` | `db` | Where counters live: `db` (`{{%userextended_rate_limit}}`, survives cache flush), `cache` (Yii cache/session), `auto` (DB if table exists else cache). **Run userextended migrations** so the table exists when using `db` (default). |
| `loginMaxAttempts` | `5` | Failures before lock. |
| `loginAttemptWindow` | `900` | Counter TTL / window (seconds). |
| `loginLockoutDuration` | `900` | Lock duration (seconds). |
| `loginProgressiveDelay` | `true` | Sleep after failed login. |
| `loginDelayBaseSeconds` | `1` | Delay = min(base × attempts, max). |
| `loginDelayMaxSeconds` | `5` | Max delay. |
| `loginCaptchaAfterAttempts` | `3` | Show Yii captcha after N failures (`0` disables). |
| `loginCaptchaAction` | `['/site/captcha']` | Captcha route. |

Failed login messages are generic (anti user enumeration).

### Registration (when `user.enableRegistration` is true)

Remove `BackendFilter` / set `blockRegistrationAndRecovery` to `false`, then enable:

| Setting | Where | Notes |
|---------|--------|--------|
| `enableRegistration` | `user` | Required |
| `enableConfirmation` | `user` | Recommended (email confirm) |
| `captcha` | `userextended` | Yii captcha on register form |
| `terms` | `userextended` | Terms checkbox |
| `enableCloudflareTurnstile` + `cloudflareTurnstileOnRegistration` | `userextended` | Optional Turnstile |
| `enableRegistrationRateLimit` | `userextended` | IP + email throttle (default on) |

Map `registration` → `cinghie\userextended\controllers\RegistrationController` for throttle on register/resend.

### Password recovery (when `user.enablePasswordRecovery` is true)

Hardened defaults are applied by Bootstrap via `UserModuleHardening` even while recovery stays disabled in CRM:

| Setting | Default | Notes |
|---------|---------|--------|
| `recoverWithin` | `3600` | Recovery token TTL (1h; Dektrium was 6h) → `user.recoverWithin` |
| `confirmWithin` | `21600` | Confirmation token TTL (6h; Dektrium was 24h) → `user.confirmWithin` |
| `enableSecureEmailChange` | `true` | Sets `user.emailChangeStrategy = STRATEGY_SECURE` |
| `mailPlaintextPasswords` | `false` | No plaintext passwords in welcome/resend mail; admin resend sends a **recovery link** (mail first, then password rotation — no lockout if mail fails) |
| `enableRecoveryRateLimit` | `true` | IP + email throttle on recovery request |

Map `recovery` → `cinghie\userextended\controllers\RecoveryController`.

| Parameter | Default | Description |
|-----------|---------|-------------|
| `blockRegistrationAndRecovery` | `false` | Bootstrap attaches `BackendFilter` on `user` if no `as backend` yet. |
| `enableRegistrationRateLimit` | `true` | Enable IP + email counters (same `rateLimitStorage` as login). |
| `registrationMaxAttempts` | `5` | Attempts before lock. |
| `registrationAttemptWindow` | `900` | Counter TTL (seconds). |
| `registrationLockoutDuration` | `900` | Lock duration (seconds). |
| `registrationProgressiveDelay` | `true` | Sleep after failed attempt. |
| `registrationDelayBaseSeconds` | `1` | Delay = min(base × attempts, max). |
| `registrationDelayMaxSeconds` | `5` | Max delay. |

### Cloudflare Turnstile (optional)

| Parameter | Default | Description |
|-----------|---------|-------------|
| `enableCloudflareTurnstile` | `false` | Show/validate Turnstile on login. Requires both keys when `true`. |
| `cloudflareSiteKey` | `''` | Public site key. |
| `cloudflareSecretKey` | `''` | Secret key (**web-local / env only**). |
| `cloudflareTurnstileTheme` | `auto` | `auto` / `light` / `dark`. |
| `cloudflareTurnstileOnRegistration` | `false` | Also require Turnstile on registration (needs `enableCloudflareTurnstile`). |

Fail closed: missing/invalid token denies login. Script is loaded only when enabled and configured. Use together with rate limit (Turnstile first, rate limit second).

### Caching / performance

| Parameter | Default | Description |
|-----------|---------|-------------|
| `enableRbacRoleCache` | `true` | Cache role name list for admin filters. |
| `rbacRoleCacheDuration` | `3600` | Cache TTL (seconds). |

Map `rbac` `RoleController` / `PermissionController` / `AssignmentController` to the userextended controllers so role-list cache is invalidated, deletes are POST-only, and assignments use the secured model.

### RBAC assignment security

| Parameter | Default | Description |
|-----------|---------|-------------|
| `blockSelfRoleAssignment` | `true` | Deny adding new roles/permissions to your own user. |
| `enableRbacAssignmentAudit` | `true` | Log assignment changes (requires `enableSecurityAudit`). |
| `enableSecurityAudit` | `true` | Structured security events (login, block/delete, Turnstile, session, RBAC) via logger or `Yii::info` (`userextended.security`). Never logs passwords/tokens. |

Assignment updates go through `AdminController::actionAssignments` (and `/rbac/assignment/assign`) with admin AccessControl, CSRF, and VerbFilter. Self-escalation attempts are blocked and audited.

Admin user grid uses eager loading for `profile` / `roles`. Impersonation (`user/admin/switch`) is disabled in this package (`AdminController` + `enableImpersonateUser => false`).

## Overrides

Override controller example, on modules config

```
'modules' => [ 
	
	'userextended' => [ 
		'class' => 'cinghie\userextended\Module',
		'controllerMap' => [
			'items' => 'app\controllers\AdminController',
			'items' => 'app\controllers\SecurityController',
			'items' => 'app\controllers\SettingsController',
		]
	]
	
],
```

Override models example, on modules config

```
'modules' => [ 
	
	'userextended' => [ 
		'class' => 'cinghie\userextended\Module',
		'modelMap' => [
			'Account' => 'app\models\Account',
			'Assignment' => 'app\models\Assignment',
			'LoginForm' => 'app\models\LoginForm',
			'Permission' => 'app\models\Permission',
			'Profile' => 'app\models\Profile',
			'RegistrationForm' => 'app\models\RegistrationForm',
			'SettingsForm' => 'app\models\SettingsForm',
			'User' => 'app\models\User',
		]
	]
	
],
```

Override view example, on components config

```
'components' => [ 

	'view' => [
		'theme' => [
			'pathMap' => [
				'@cinghie/userextended/views/admin' => '@app/views/userextended/admin',
			],
		],
	],
	
],
```

Features
-----------------

<ol>
    <li>Add new fields to user profile (optional params)
        <ul>
        	<li>avatar:
            	<ol>
                	<li>The avatar can be uploaded</li>
                    <li>The avatar can be updated</li>
                    <li>On update avatar old image was deleted</li>
                    <li>Hardened upload (MIME/extension whitelist, max size, safe rename, path confinement)</li>
                </ol>
            </li>
            <li>birthday</li>
            <li>captcha</li>
            <li>firstname</li>
            <li>lastname</li>
            <li>name (firstname + lastname)</li>
            <li>signature</li>
            <li>terms</li>
        </ul>
    </li>
    <li>Add yii2-user fields to user profile like optional params
        <ul>
            <li>bio</li>
            <li>gravatar email</li>
            <li>location</li>
            <li>public email</li>
            <li>website</li>
        </ul>
    </li>
    <li>Add default Role on User Registration</li>
    <li>Session timeout with optional client redirect / warning</li>
    <li>Login brute-force protection (rate limit, lockout, progressive delay, captcha after N failures)</li>
    <li>Registration throttle (IP + email) + BackendFilter for CRM/backend (blocks registration/recovery)</li>
    <li>RBAC assignment hardening (CSRF, admin-only, block self-escalation, audit)</li>
    <li>Structured security audit (login, block/delete, Turnstile, session expire; no secrets)</li>
    <li>Optional Cloudflare Turnstile on login (and registration)</li>
    <li>Admin users grid performance (eager loading, role cache, DB indexes)</li>
    <li>User impersonation disabled by default</li>
</ol>

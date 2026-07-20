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

This also applies admin performance indexes (e.g. `user.last_login_at`).

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

        // Session expire / auth hardening
        'sessionTimeout' => 3600, // seconds; 0 disables module session handling
        'useAbsoluteAuthTimeout' => false, // if true and absoluteAuthTimeout=0, use sessionTimeout
        'absoluteAuthTimeout' => 0, // max login duration in seconds (0 = off unless useAbsoluteAuthTimeout)
        'enableClientSessionExpireRedirect' => true,
        'clientWarningBeforeExpire' => 60, // 0 disables warning
        'disableAutoLogin' => true, // CRM recommended: no remember-me
        'hardenSessionCookies' => true,
        'sessionCookieSecure' => null, // null = auto (HTTPS or prod), true/false = force
        'sessionSameSite' => null, // null = Lax when not already set
        'regenerateSessionId' => true,
        'invalidateRememberMeOnAuthTimeout' => true,

        // Login rate limit / brute-force
        'enableLoginRateLimit' => true,
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

### Session expire

| Parameter | Default | Description |
|-----------|---------|-------------|
| `sessionTimeout` | `3600` | Idle session/auth timeout in seconds. `0` disables module session handling. |
| `useAbsoluteAuthTimeout` | `false` | If `absoluteAuthTimeout` is `0`, also set `user.absoluteAuthTimeout` to `sessionTimeout`. |
| `absoluteAuthTimeout` | `0` | Max login duration in seconds (`0` = off unless `useAbsoluteAuthTimeout`). |
| `enableClientSessionExpireRedirect` | `true` | Client JS redirects to login with `?expired=1`. |
| `clientWarningBeforeExpire` | `60` | Warning seconds before expire (`0` = off). |
| `disableAutoLogin` | `true` | Disable remember-me so idle `authTimeout` works (CRM recommended). |
| `hardenSessionCookies` | `true` | Apply HttpOnly / Secure / SameSite on session (and identity) cookies when missing. |
| `sessionCookieSecure` | `null` | `null` = auto (HTTPS or prod); `true`/`false` force Secure. |
| `sessionSameSite` | `null` | SameSite when unset (`null` → `Lax`). |
| `regenerateSessionId` | `true` | Extra session ID regenerate after login (logout uses Yii `logout(true)`). |
| `invalidateRememberMeOnAuthTimeout` | `true` | Use `WebUser` so timeout clears remember-me without cookie re-login. |

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
| `passwordMinLength` | `8` | Minimum length. |
| `passwordMaxLength` | `72` | Maximum length (bcrypt limit). |
| `passwordRequireUppercase` | `true` | Require A–Z. |
| `passwordRequireLowercase` | `true` | Require a–z. |
| `passwordRequireDigit` | `true` | Require 0–9. |
| `passwordRequireSpecial` | `false` | Require non-alphanumeric. |
| `passwordBanCommon` | `true` | Reject common passwords. |
| `passwordCommonList` | `[]` | Extra banned passwords. |
| `passwordMaxAgeDays` | `0` | Force change after N days (`0` = off). Requires migration `password_changed_at`. |

Hashing and verification use only `dektrium\user\helpers\Password` (Yii `security`). Map `RecoveryForm` to `cinghie\userextended\models\RecoveryForm`.

### Avatar upload security

| Parameter | Default | Description |
|-----------|---------|-------------|
| `avatarAllowedExtensions` | `jpg,jpeg,png,webp` | Allowed extensions. |
| `avatarMaxSize` | `2097152` | Max upload size in bytes (2MB). |

Uploads are renamed randomly, MIME/`getimagesize` checked, double extensions blocked, path confined under `avatarPath`.

### Login rate limit

| Parameter | Default | Description |
|-----------|---------|-------------|
| `enableLoginRateLimit` | `true` | Enable IP + username/email counters (uses `cache`, falls back to session). |
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

| Parameter | Default | Description |
|-----------|---------|-------------|
| `blockRegistrationAndRecovery` | `false` | Bootstrap attaches `BackendFilter` on `user` if no `as backend` yet. |
| `enableRegistrationRateLimit` | `true` | Enable IP + email counters. |
| `registrationMaxAttempts` | `5` | Attempts before lock. |
| `registrationAttemptWindow` | `900` | Counter TTL (seconds). |
| `registrationLockoutDuration` | `900` | Lock duration (seconds). |
| `registrationProgressiveDelay` | `true` | Sleep after failed attempt. |
| `registrationDelayBaseSeconds` | `1` | Delay = min(base × attempts, max). |
| `registrationDelayMaxSeconds` | `5` | Max delay. |

### Cloudflare Turnstile (optional)

| Parameter | Default | Description |
|-----------|---------|-------------|
| `enableCloudflareTurnstile` | `false` | Show/validate Turnstile on login. |
| `cloudflareSiteKey` | `''` | Public site key. |
| `cloudflareSecretKey` | `''` | Secret key (**web-local / env only**). |
| `cloudflareTurnstileTheme` | `auto` | `auto` / `light` / `dark`. |
| `cloudflareTurnstileOnRegistration` | `false` | Also require Turnstile on registration. |

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
| `enableRbacAssignmentAudit` | `true` | Log assignment changes (cinghie logger if present, else `Yii::info` category `userextended.security`). |

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
    <li>Optional Cloudflare Turnstile on login (and registration)</li>
    <li>Admin users grid performance (eager loading, role cache, DB indexes)</li>
    <li>User impersonation disabled by default</li>
</ol>

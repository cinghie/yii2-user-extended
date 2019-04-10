# Yii2 User Extended

![Latest Stable Version](https://img.shields.io/packagist/v/cinghie/yii2-user-extended.svg)
![License](https://img.shields.io/packagist/l/cinghie/yii2-user-extended.svg)
![Latest Release Date](https://img.shields.io/github/release-date/cinghie/yii2-user-extended.svg)
![Latest Commit](https://img.shields.io/github/last-commit/cinghie/yii2-user-extended.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/cinghie/yii2-user-extended.svg)](https://packagist.org/packages/cinghie/yii2-user-extended)

Yii2 User Extended to extend Yii2 User by Dektrium: https://github.com/dektrium/yii2-user

This is not an standalone module to manage users but a module to extend Yii2 User extension.

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

```
$ php yii migrate/up --migrationPath=@yii/rbac/migrations
```

### 4. Update yii2 user extended database schema

```
$ php yii migrate/up --migrationPath=@vendor/cinghie/yii2-user-extended/migrations
```

### 5. Set configuration file

Set on your configuration file, in modules section

```
'modules' =>  [
    // Yii2 RBAC
    'rbac' => [
        'class' => 'dektrium\rbac\Module'
    ],
    // Yii2 User
    'user' => [
        'class' => 'dektrium\user\Module',
        // Yii2 User Controllers Overrides
        'controllerMap' => [
            'admin' => 'cinghie\userextended\controllers\AdminController',
            'security' => 'cinghie\userextended\controllers\SecurityController',
            'settings' => 'cinghie\userextended\controllers\SettingsController'
        ],
        // Yii2 User Models Overrides
        'modelMap' => [
            'RegistrationForm' => 'cinghie\userextended\models\RegistrationForm',
            'Profile' => 'cinghie\userextended\models\Profile',
            'SettingsForm' => 'cinghie\userextended\models\SettingsForm',
            'User' => 'cinghie\userextended\models\User',
        ],
    ],
    // Yii2 User Extended
    'userextended' => [
        'class' => 'cinghie\userextended\Module',
        'avatarPath' => '@webroot/img/users/', // Path to your avatar files
        'avatarURL' => '@web/img/users/', // Url to your avatar files
        'defaultRole' => '', // example 'registered'
        'avatar' => true,
        'bio' => false,
        'captcha' => true,
        'birthday' => true,
        'firstname' => true,
        'gravatarEmail' => false,
        'lastname' => true,
        'location' => false,
        'onlyEmail' => false,
        'publicEmail' => false,
        'signature' => true,
        'templateLogin' => 'login_prestashop', // login or login_prestashop
        'templateLogoURL' => '@web/logo.png', // Url to logo
        'templateRegister' => '_two_column', // _one_column or _two_column
        'terms' => true,
        'website' => false,
        'showTitles' => true, // Set false in adminLTE
    ],
]
```

and in components section

```
'components' =>  [
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

If you have a Yii2 App Advanced add in Yii2 User Module config

```
'modules' =>  [

    'user' => [
        'class' => 'dektrium\user\Module',
        // restrict access to recovery and registration controllers from backend
        'as backend' => 'dektrium\user\filters\BackendFilter',
        // Settings
        'enableRegistration' => false,
    ],
    
],		
```

Or use userextended filter that active profile and settings on backend (dektrium filter disable it)

```
'modules' =>  [

    'user' => [
        'class' => 'dektrium\user\Module',
        // restrict access to recovery and registration controllers from backend
        'as backend' => 'cinghie\userextended\filters\BackendFilter',
        // Settings
        'enableRegistration' => false,
    ],

],
```

### 6. Set captcha in Controller

in your SiteController set in actions() function

```
'captcha' => [
    'class' => 'yii\captcha\CaptchaAction',
    'minLength' => 6,
    'maxLength' => 6
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
</ol>

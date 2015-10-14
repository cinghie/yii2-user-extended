# Yii2 User Extended
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

Copy img folder to your webroot

Set on your configuration file, in modules section

```
'modules' =>  [
        'user' => [
                'class' => 'dektrium\user\Module',
				// Settings
				'enableConfirmation' => true,
				'enableRegistration' => true,
				// Settings Mailer
				'mailer' => [
					'sender'                => 'no-reply@myhost.com', // or ['no-reply@myhost.com' => 'Sender name']
					'welcomeSubject'        => 'Welcome subject',
					'confirmationSubject'   => 'Confirmation subject',
					'reconfirmationSubject' => 'Email change subject',
					'recoverySubject'       => 'Recovery subject',
				],
                // Yii2 User Controllers Overrides
				'controllerMap' => [
					'settings' => 'cinghie\yii2userextended\controllers\SettingsController'
				],
				// Yii2 User Models Overrides
				'modelMap' => [
					'RegistrationForm' => 'cinghie\yii2userextended\models\RegistrationForm',
					'Profile'          => 'cinghie\yii2userextended\models\Profile',
					'SettingsForm'     => 'cinghie\yii2userextended\models\SettingsForm',
					'User'             => 'cinghie\yii2userextended\models\User',
				],
        ],
]
```

and in components section

```
'components' =>  [
        'view' => [
                'theme' => [
                        'pathMap' => [
                                '@dektrium/user/views' => '@vendor/cinghie/yii2-user-extended/views'
                        ],
                ],
        ],
]
```

in your SiteController set in actions() function

```
'captcha' => [
        'class' => 'yii\captcha\CaptchaAction',
        'minLength' => 6,
        'maxLength' => 6
],
```

and update your database tables running SQL file in docs

Features
-----------------

Add new fields to user profile
<ul>
  <li>firstname</li>
  <li>lastname</li>
  <li>name (firstname + lastname)</li>  
  <li>birthday</li>
  <li>terms</li>
  <li>captcha</li>
  <li>avatar</li>  
</ul>

Changelog
-----------------

<ul>
  <li>Version 0.3.4 - Delete old avatar on updating</li>
  <li>Version 0.3.1 - Update Avatar</li>
  <li>Version 0.3.0 - Adding Avatar</li>
  <li>Version 0.2.1 - Adding Captcha</li>
  <li>Version 0.2.0 - Fixed Bugs</li>
  <li>Version 0.1.5 - Fixing register view, Adding Birthday Field</li>
  <li>Version 0.1.4 - Fixed Bugs</li>
  <li>Version 0.1.3 - Fix User Namespace</li>
  <li>Version 0.1.2 - Fixed Extension</li>
  <li>Version 0.1.1 - Update Composer</li>
  <li>Version 0.1.0 - Initial Release</li>
</ul>

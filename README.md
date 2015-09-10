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

Set on your configuration file, in modules section

```
'modules' =>  [
        'user' => [
                'class' => 'dektrium\user\Module',
                'modelMap' => [
                    'RegistrationForm' => 'cinghie\yii2userextended\models\RegistrationForm',
                    'Profile' => 'cinghie\yii2userextended\models\Profile',
                    'User' => 'cinghie\yii2userextended\models\User',
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

and update your database tables running SQL file in docs

Features
-----------------

Add new fields to user profile and on registration view
<ul>
  <li>firstname</li>
  <li>lastname</li>
  <li>birthday</li>
  <li>terms</li>
</ul>

Changelog
-----------------

<ul>
  <li>Version 0.2.0 - Fixed Bugs</li>
  <li>Version 0.1.5 - Fixing register view, Adding Birthday Field</li>
  <li>Version 0.1.4 - Fixed Bugs</li>
  <li>Version 0.1.3 - Fix User Namespace</li>
  <li>Version 0.1.2 - Fixed Extension</li>
  <li>Version 0.1.1 - Update Composer</li>
  <li>Version 0.1.0 - Initial Release</li>
</ul>
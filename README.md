# Yii2 User Extended
Yii2 User Extended to extend Yii2 User by Dektrium: https://github.com/dektrium/yii2-user

This is not an standalone module to manage users but a module to extend Yii2 User.

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
'components' =>  [
        'view' => [
                'theme' => [
                        'pathMap' => [
                                '@dektrium/user/views' => '@cinghie/yii2-user-extended/views'
                        ],
                ],
        ],
]
```

and in components section

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

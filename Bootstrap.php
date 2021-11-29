<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.3
 */

namespace cinghie\userextended;

use Yii;
use cinghie\userextended\models\Account;
use cinghie\userextended\models\Assignment;
use cinghie\userextended\models\LoginForm;
use cinghie\userextended\models\Permission;
use cinghie\userextended\models\Profile;
use cinghie\userextended\models\RegistrationForm;
use cinghie\userextended\models\SettingsForm;
use cinghie\userextended\models\User;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Module;
use yii\db\ActiveRecord;

/**
 * Bootstrap class
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * @var array
     */
    private $_modelMap = [
        'Account' => Account::class,
        'Assignment' => Assignment::class,
        'LoginForm' => LoginForm::class,
        'Permission' => Permission::class,
        'Profile' => Profile::class,
        'RegistrationForm' => RegistrationForm::class,
        'SettingsForm' => SettingsForm::class,
        'User' => User::class,
    ];

	/**
	 * @param Application $app
	 */
    public function bootstrap($app)
    {
        /**
         * @var Module $module
         * @var ActiveRecord $modelName
         */
        if ($app->hasModule('userextended') && ($module = $app->getModule('userextended')) instanceof Module)
        {
            $this->_modelMap = array_merge($this->_modelMap, $module->modelMap);

            foreach ($this->_modelMap as $name => $definition)
            {
                $class = "cinghie\\userextended\\models\\" . $name;

                Yii::$container->set($class, $definition);
                $modelName = is_array($definition) ? $definition['class'] : $definition;
                $module->modelMap[$name] = $modelName;

                if (in_array($name,['Account','Assignment','LoginForm','Permission','Profile','RegistrationForm','SettingsForm','User']))
                {
                    Yii::$container->set($name . 'Query', function () use ($modelName) {
                        return $modelName::find();
                    });
                }
            }
        }
    }
}

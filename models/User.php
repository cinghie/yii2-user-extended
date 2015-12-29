<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.5.0
 */

namespace cinghie\yii2userextended\models;

use cinghie\yii2userextended\models\Profile;
use dektrium\user\models\User as BaseUser;

class User extends BaseUser
{

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProfile()
    {
        return $this->hasOne($this->module->modelMap['Profile'], ['user_id' => 'id'])->from(Profile::tableName() . ' AS profile');
    }

}
<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.1.0
 */

namespace cinghie\yii2userextended\models;

use dektrium\user\models\Profile as BaseProfile;

class Profile extends BaseProfile
{

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        // add firstname to scenarios
        $scenarios['create'][]   = 'firstname';
        $scenarios['update'][]   = 'firstname';
        $scenarios['register'][] = 'firstname';

        // add lastname to scenarios
        $scenarios['create'][]   = 'lastname';
        $scenarios['update'][]   = 'lastname';
        $scenarios['register'][] = 'lastname';

        return $scenarios;
    }

    public function rules()
    {
        $rules = parent::rules();

        // add firstname rules
        $rules['firstnameRequired'] = ['firstname', 'required'];
        $rules['firstnameLength']   = ['firstname', 'string', 'max' => 255];

        // add lastname rules
        $rules['lastnameRequired']  = ['lastname', 'required'];
        $rules['lastnameLength']    = ['lastname', 'string', 'max' => 255];

        return $rules;
    }

}

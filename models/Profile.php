<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.4.0
 */

namespace cinghie\yii2userextended\models;

use Yii;
use yii\web\UploadedFile;

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

        // add birthday to scenarios
        $scenarios['create'][]   = 'birthday';
        $scenarios['update'][]   = 'birthday';
        $scenarios['register'][] = 'birthday';

        // add avatar to scenarios
        $scenarios['create'][]   = 'avatar';
        $scenarios['update'][]   = 'avatar';
        $scenarios['register'][]   = 'avatar';

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

        // add birthday rules
        $rules['birthdayRequired']  = ['birthday', 'required'];
        $rules['birthdayLength']    = ['birthday', 'date', 'format' => 'yyyy-mm-dd'];

        // add terms checkbox
        $rules['termsRequired']     = ['terms', 'required', 'requiredValue' => true, 'message' => 'You must agree to the terms and conditions'];
        $rules['termsLength']       = ['terms', 'integer'];

        return $rules;
    }

    /**
     * Upload file
     *
     * @return mixed the uploaded image instance
     */
    public function uploadAvatar($filePath)
    {
        $file = UploadedFile::getInstance($this, 'avatar');

        // if no file was uploaded abort the upload
        if (empty($file)) {
            return false;
        } else {

            // file extension
            $fileExt = end((explode(".", $file->name)));
            // purge filename
            $fileName = Yii::$app->security->generateRandomString();
            // update file->name
            $file->name = $fileName.".{$fileExt}";
            // update avatar field
            $this->avatar = $fileName.".{$fileExt}";
            // save images to imagePath
            $file->saveAs($filePath.$fileName.".{$fileExt}");

            // the uploaded file instance
            return $file;
        }
    }

    /**
     * fetch stored image file name with complete path
     *
     * @return string
     */
    public function getImagePath()
    {
        return $this->avatar ? Yii::getAlias(Yii::$app->getModule('userextended')->avatarPath).$this->avatar : null;
    }

    /**
     * fetch stored image url
     *
     * @return string
     */
    public function getImageUrl()
    {
        $avatar = $this->avatar ? $this->avatar : 'default.png';
        return Yii::getAlias(Yii::$app->getModule('userextended')->avatarURL).$avatar;
    }

    /**
     * Process deletion of image
     *
     * @return boolean the status of deletion
     */
    public function deleteImage($avatarOld)
    {
        $avatarURL = Yii::getAlias(Yii::$app->getModule('userextended')->avatarPath).$avatarOld;

        // check if file exists on server
        if (empty($avatarURL) || !file_exists($avatarURL)) {
            return false;
        }

        // check if uploaded file can be deleted on server
        if (!unlink($avatarURL)) {
            return false;
        }

        // if deletion successful, reset your file attributes
        $this->avatar = null;

        return true;
    }

}

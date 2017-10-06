<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.0
 */

namespace cinghie\userextended\models;

use dektrium\user\models\Profile as BaseProfile;
use yii\web\UploadedFile;

class Profile extends BaseProfile
{

    public function scenarios()
    {
        $scenarios = parent::scenarios();

	    if(\Yii::$app->getModule('userextended')->birthday) {
		    $scenarios['create'][]   = 'birthday';
		    $scenarios['update'][]   = 'birthday';
		    $scenarios['register'][] = 'birthday';
	    }

	    if(\Yii::$app->getModule('userextended')->firstname) {
		    $scenarios['create'][]   = 'firstname';
		    $scenarios['update'][]   = 'firstname';
		    $scenarios['register'][] = 'firstname';
	    }

	    if(\Yii::$app->getModule('userextended')->lastname) {
		    $scenarios['create'][]   = 'lastname';
		    $scenarios['update'][]   = 'lastname';
		    $scenarios['register'][] = 'lastname';
	    }

        // add avatar to scenarios
        $scenarios['create'][]   = 'avatar';
        $scenarios['update'][]   = 'avatar';
        $scenarios['register'][] = 'avatar';

        return $scenarios;
    }

    public function rules()
    {
        $rules = parent::rules();

	    if(\Yii::$app->getModule('userextended')->birthday) {
		    $rules['birthdayRequired']  = ['birthday', 'required'];
		    $rules['birthdayLength']    = ['birthday', 'date', 'format' => 'yyyy-mm-dd'];
	    }

	    if(\Yii::$app->getModule('userextended')->firstname) {
		    $rules['firstnameRequired'] = ['firstname', 'required'];
		    $rules['firstnameLength']   = ['firstname', 'string', 'max' => 255];
	    }

	    if(\Yii::$app->getModule('userextended')->lastname) {
		    $rules['lastnameRequired'] = ['lastname', 'required'];
		    $rules['lastnameLength']   = ['lastname', 'string', 'max' => 255];
	    }

        return $rules;
    }

    /** @inheritdoc */
    public function attributeLabels()
    {
        return [
            'name' => \Yii::t('userextended', 'Name'),
            'firstname' => \Yii::t('userextended', 'Firstname'),
            'lastname' => \Yii::t('userextended', 'Lastname'),
            'birthday' => \Yii::t('userextended', 'Birthday'),
        ];
    }

    /**
     * Upload file
     *
     * @param $filePath
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
            $fileExt = $file->extension;
            // purge filename
            $fileName = \Yii::$app->security->generateRandomString();
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
        return $this->avatar ? \Yii::getAlias(\Yii::$app->getModule('userextended')->avatarPath).$this->avatar : null;
    }

    /**
     * fetch stored image url
     *
     * @return string
     */
    public function getImageUrl()
    {
        if ( !is_null($this->getAccountAttributes()) && !$this->avatar )
        {
            $imageURL = $this->getSocialImage();

        } else {

            $avatar   = $this->avatar ? $this->avatar : 'default.png';
            $imageURL = \Yii::getAlias(\Yii::$app->getModule('userextended')->avatarURL).$avatar;
        }

        return $imageURL;
    }

    /**
     * Process deletion of image
     *
     * @return boolean the status of deletion
     */
    public function deleteImage($avatarOld)
    {
        $avatarURL = \Yii::getAlias(\Yii::$app->getModule('userextended')->avatarPath).$avatarOld;

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

    public function getSocialImage()
    {
        $imageURL = "";
        $account  = $this->getAccountAttributes();

        switch($account['provider'])
        {
            case "facebook":
                $imageURL = "https://graph.facebook.com/".$account['client_id']."/picture?type=large";
                break;
        }

        return $imageURL;
    }

    /**
     * @return \yii\db\ActiveQueryInterface
     */
    public function getAccount()
    {
        return $this->hasOne($this->module->modelMap['Account'], ['user_id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQueryInterface
     */
    public function getAccountAttributes()
    {
        return $this->hasOne($this->module->modelMap['Account'], ['user_id' => 'user_id'])->asArray()->one();
    }

}

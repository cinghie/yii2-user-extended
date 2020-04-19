<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.2
 */

namespace cinghie\userextended\models;

use Yii;
use cinghie\traits\EditorTrait;
use dektrium\user\models\Profile as BaseProfile;
use yii\base\Exception;
use yii\base\InvalidParamException;
use yii\db\ActiveQueryInterface;
use yii\web\UploadedFile;

/**
 * Class Profile
 *
 * @property string $imagePath
 * @property string $imageUrl
 * @property string $socialImage
 * @property ActiveQueryInterface $account
 * @property Profile $accountAttributes
 */
class Profile extends BaseProfile
{
	use EditorTrait;

	/**
	 * @inheritdoc
	 */
    public function scenarios()
    {
        $scenarios = parent::scenarios();

	    if(Yii::$app->getModule('userextended')->avatar) {
		    $scenarios['create'][]   = 'avatar';
		    $scenarios['update'][]   = 'avatar';
		    $scenarios['register'][] = 'avatar';
	    }

	    if(Yii::$app->getModule('userextended')->birthday) {
		    $scenarios['create'][]   = 'birthday';
		    $scenarios['update'][]   = 'birthday';
		    $scenarios['register'][] = 'birthday';
	    }

	    if(Yii::$app->getModule('userextended')->firstname) {
		    $scenarios['create'][]   = 'firstname';
		    $scenarios['update'][]   = 'firstname';
		    $scenarios['register'][] = 'firstname';
	    }

	    if(Yii::$app->getModule('userextended')->lastname) {
		    $scenarios['create'][]   = 'lastname';
		    $scenarios['update'][]   = 'lastname';
		    $scenarios['register'][] = 'lastname';
	    }

	    if(Yii::$app->getModule('userextended')->signature) {
		    $scenarios['create'][]   = 'signature';
		    $scenarios['update'][]   = 'signature';
		    $scenarios['register'][] = 'signature';
	    }

        return $scenarios;
    }

	/**
	 * @inheritdoc
	 */
    public function rules()
    {
        $rules = parent::rules();

	    if(Yii::$app->getModule('userextended')->birthday) {
		    $rules['birthdayLength'] = ['birthday', 'date', 'format' => 'yyyy-mm-dd'];
		    $rules['birthdayRequired'] = ['birthday', 'required'];
		    $rules['birthdayTrim'] = ['birthday', 'trim'];
	    }

	    if(Yii::$app->getModule('userextended')->firstname) {
		    $rules['firstnameLength'] = ['firstname', 'string', 'max' => 255];
		    $rules['firstnameRequired'] = ['firstname', 'required'];
		    $rules['firstnameTrim'] = ['firstname', 'trim'];
	    }

	    if(Yii::$app->getModule('userextended')->lastname) {
		    $rules['lastnameLength'] = ['lastname', 'string', 'max' => 255];
		    $rules['lastnameRequired'] = ['lastname', 'required'];
		    $rules['lastnameTrim'] = ['lastname', 'trim'];
	    }

	    if(Yii::$app->getModule('userextended')->signature) {
		    $rules['signatureLength'] = ['signature', 'string'];
		    $rules['signatureTrim'] = ['signature', 'trim'];
	    }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'avatar' => Yii::t('userextended', 'Avatar'),
            'birthday' => Yii::t('userextended', 'Birthday'),
            'firstname' => Yii::t('userextended', 'Firstname'),
            'lastname' => Yii::t('userextended', 'Lastname'),
            'name' => Yii::t('userextended', 'Name'),
            'signature' => Yii::t('userextended', 'Signature'),
        ];
    }

	/**
	 * Upload file
	 *
	 * @param string $filePath
	 *
	 * @return mixed
	 * @throws Exception
	 */
    public function uploadAvatar($filePath)
    {
        $file = UploadedFile::getInstance($this, 'avatar');

        // if no file was uploaded abort the upload
        if ( null === $file ) {
            return false;
        }

		// file extension
	    $fileExt = $file->extension;
	    // purge filename
	    $fileName = Yii::$app->security->generateRandomString();
	    // update file->name
	    $file->name = $fileName.".{$fileExt}";
	    // update avatar field
	    $this->avatar = $fileName.".{$fileExt}";
	    // save images to imagePath
	    $file->saveAs($filePath.$fileName.".{$fileExt}");

	    return $file;
    }

	/**
	 * fetch stored image file name with complete path
	 *
	 * @return string
	 * @throws InvalidParamException
	 */
    public function getImagePath()
    {
        return $this->avatar ? Yii::getAlias(Yii::$app->getModule('userextended')->avatarPath).$this->avatar : null;
    }

	/**
	 * fetch stored image url
	 *
	 * @return string
	 * @throws InvalidParamException
	 */
    public function getImageUrl()
    {
	    if ( !$this->avatar && $this->getAccountAttributes() !== null )
        {
            $imageURL = $this->getSocialImage();

        } else {

            $avatar   = $this->avatar ?: 'default.png';
            $imageURL = Yii::getAlias(Yii::$app->getModule('userextended')->avatarURL).$avatar;
        }

        return $imageURL;
    }

	/**
	 * Process deletion of image
	 *
	 * @param string $avatarOld
	 *
	 * @return bool
	 * @throws InvalidParamException
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

    /**
     * Get image form Social
     *
     * @return string
     */
    public function getSocialImage()
    {
        $account  = $this->getAccountAttributes();

        switch($account['provider'])
        {
	        case 'facebook':
		        /** @var Account $account */
		        $imageURL = 'https://graph.facebook.com/' . $account['client_id'] . '/picture?type=large';
		        break;
	        case 'twitter':
		        /** @var Account $account */
		        $imageURL = '';
		        break;
	        default:
		        $imageURL = null;
        }

        return $imageURL;
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getAccount()
    {
        return $this->hasOne($this->module->modelMap['Account'], ['user_id' => 'user_id']);
    }

	/**
	 * @return Profile []
	 */
    public function getAccountAttributes()
    {
        return $this->hasOne($this->module->modelMap['Account'], ['user_id' => 'user_id'])->asArray()->one();
    }
}

<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.4
 */

namespace cinghie\userextended\models;

use Yii;
use cinghie\traits\EditorTrait;
use cinghie\userextended\helpers\ModuleConfig;
use cinghie\userextended\helpers\SafeHtml;
use dektrium\user\models\Profile as BaseProfile;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\db\ActiveQueryInterface;
use yii\helpers\FileHelper;
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
	 * MIME → canonical extension used when storing avatars.
	 *
	 * @var array<string, string>
	 */
	private static $avatarMimeMap = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/webp' => 'webp',
	];

	/**
	 * Canonical extension → accepted client extensions.
	 *
	 * @var array<string, string[]>
	 */
	private static $avatarExtensionAliases = [
		'jpg' => ['jpg', 'jpeg'],
		'png' => ['png'],
		'webp' => ['webp'],
	];

	/**
	 * Validation error from the last avatar upload attempt (applied in beforeValidate).
	 *
	 * @var string|null
	 */
	private $avatarUploadError;

	/**
	 * @inheritdoc
	 */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
	    $base = isset($scenarios[self::SCENARIO_DEFAULT]) ? $scenarios[self::SCENARIO_DEFAULT] : [];
	    foreach (['create', 'update', 'register'] as $scenario) {
		    $scenarios[$scenario] = $base;
	    }

	    $cfg = ModuleConfig::snapshot();

	    if (ModuleConfig::get('account')) {
		    $scenarios['default'][]  = 'account';
		    $scenarios['create'][]   = 'account';
		    $scenarios['update'][]   = 'account';
		    $scenarios['register'][] = 'account';
	    }

	    if (!empty($cfg['avatar'])) {
		    $scenarios['default'][]  = 'avatar';
		    $scenarios['create'][]   = 'avatar';
		    $scenarios['update'][]   = 'avatar';
		    $scenarios['register'][] = 'avatar';
	    }

	    if (!empty($cfg['birthday'])) {
		    $scenarios['default'][]  = 'birthday';
		    $scenarios['create'][]   = 'birthday';
		    $scenarios['update'][]   = 'birthday';
		    $scenarios['register'][] = 'birthday';
	    }

	    if (ModuleConfig::get('contact')) {
		    $scenarios['default'][]  = 'contact';
		    $scenarios['create'][]   = 'contact';
		    $scenarios['update'][]   = 'contact';
		    $scenarios['register'][] = 'contact';
	    }

	    if (!empty($cfg['firstname'])) {
		    $scenarios['default'][]  = 'firstname';
		    $scenarios['create'][]   = 'firstname';
		    $scenarios['update'][]   = 'firstname';
		    $scenarios['register'][] = 'firstname';
	    }

	    if (!empty($cfg['lastname'])) {
		    $scenarios['default'][]  = 'lastname';
		    $scenarios['create'][]   = 'lastname';
		    $scenarios['update'][]   = 'lastname';
		    $scenarios['register'][] = 'lastname';
	    }

	    if (!empty($cfg['signature'])) {
		    $scenarios['default'][]  = 'signature';
		    $scenarios['create'][]   = 'signature';
		    $scenarios['update'][]   = 'signature';
		    $scenarios['register'][] = 'signature';
	    }

	    foreach ($scenarios as $name => $attrs) {
		    $scenarios[$name] = array_values(array_unique($attrs));
	    }

        return $scenarios;
    }

	/**
	 * @inheritdoc
	 */
    public function rules()
    {
        $rules = parent::rules();
	    $cfg = ModuleConfig::snapshot();

	    if (ModuleConfig::get('account')) {
		    $rules['accountFilter'] = ['account', 'filter', 'filter' => function ($value) {
			    return $value === '' || $value === null ? null : $value;
		    }];
		    $rules['accountInteger'] = ['account', 'integer', 'skipOnEmpty' => true];
	    }

	    if (!empty($cfg['birthday'])) {
		    $rules['birthdayLength'] = ['birthday', 'date', 'format' => 'yyyy-mm-dd'];
		    $rules['birthdayRequired'] = ['birthday', 'required'];
		    $rules['birthdayTrim'] = ['birthday', 'trim'];
	    }

	    if (ModuleConfig::get('contact')) {
		    $rules['contactFilter'] = ['contact', 'filter', 'filter' => function ($value) {
			    return $value === '' || $value === null ? null : $value;
		    }];
		    $rules['contactInteger'] = ['contact', 'integer', 'skipOnEmpty' => true];
	    }

	    if (!empty($cfg['firstname'])) {
		    $rules['firstnameLength'] = ['firstname', 'string', 'max' => 255];
		    $rules['firstnameRequired'] = ['firstname', 'required'];
		    $rules['firstnameTrim'] = ['firstname', 'trim'];
	    }

	    if (!empty($cfg['lastname'])) {
		    $rules['lastnameLength'] = ['lastname', 'string', 'max' => 255];
		    $rules['lastnameRequired'] = ['lastname', 'required'];
		    $rules['lastnameTrim'] = ['lastname', 'trim'];
	    }

	    if (!empty($cfg['signature'])) {
		    $rules['signatureLength'] = ['signature', 'string'];
		    $rules['signatureTrim'] = ['signature', 'trim'];
		    $rules['signatureSanitize'] = [
			    'signature',
			    'filter',
			    'filter' => [SafeHtml::class, 'sanitizeSignature'],
		    ];
	    }

	    // Bio is always stored as plain text (no HTML)
	    if (isset($rules['bioString']) || property_exists($this, 'bio')) {
		    $rules['bioPlain'] = [
			    'bio',
			    'filter',
			    'filter' => [SafeHtml::class, 'plainText'],
			    'skipOnEmpty' => true,
		    ];
	    }

	    // Strip HTML from common text profile fields when present
	    foreach (['name', 'firstname', 'lastname', 'location', 'public_email', 'website'] as $plainField) {
		    $rules[$plainField . 'Plain'] = [
			    $plainField,
			    'filter',
			    'filter' => static function ($value) {
				    if ($value === null || $value === '') {
					    return $value;
				    }
				    return SafeHtml::plainText($value);
			    },
			    'skipOnEmpty' => true,
		    ];
	    }

        return $rules;
    }

	/**
	 * Signature ready for safe HTML rendering.
	 *
	 * @return string
	 */
	public function getSignatureHtml()
	{
		return SafeHtml::formatSignature($this->signature);
	}

	/**
	 * Bio ready for safe HTML rendering.
	 *
	 * @return string
	 */
	public function getBioHtml()
	{
		return SafeHtml::formatBio($this->bio);
	}

	/**
	 * @inheritdoc
	 */
	public function beforeValidate()
	{
		if (ModuleConfig::get('avatar') && $this->avatarUploadError !== null) {
			$this->addError('avatar', $this->avatarUploadError);
		}

		return parent::beforeValidate();
	}

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'account' => Yii::t('traits', 'Account'),
            'avatar' => Yii::t('userextended', 'Avatar'),
            'birthday' => Yii::t('userextended', 'Birthday'),
            'contact' => Yii::t('traits', 'Contact'),
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
	 * @return false|UploadedFile
     * @throws Exception
	 */
    public function uploadAvatar($filePath)
    {
	    $this->avatarUploadError = null;

        $file = UploadedFile::getInstance($this, 'avatar');

        // if no file was uploaded abort the upload
        if ($file === null) {
            return false;
        }

	    $safeExt = $this->validateAvatarUpload($file);
	    if ($safeExt === false) {
		    return false;
	    }

	    $basePath = $this->resolveAvatarDirectory($filePath, true);
	    if ($basePath === false) {
		    $this->avatarUploadError = Yii::t('userextended', 'Avatar upload path is not writable.');
		    return false;
	    }

	    $fileName = Yii::$app->security->generateRandomString(32);
	    $finalName = $fileName . '.' . $safeExt;
	    $targetPath = $basePath . DIRECTORY_SEPARATOR . $finalName;

	    if (!$this->isPathInsideDirectory($basePath, $targetPath)) {
		    $this->avatarUploadError = Yii::t('userextended', 'Invalid avatar file.');
		    return false;
	    }

	    if (!$file->saveAs($targetPath)) {
		    $this->avatarUploadError = Yii::t('userextended', 'Avatar upload failed.');
		    return false;
	    }

	    $file->name = $finalName;
	    $this->avatar = $finalName;

	    return $file;
    }

	/**
	 * Validate uploaded avatar: extension, MIME, size, double extension, real image.
	 *
	 * @param UploadedFile $file
	 *
	 * @return string|false Canonical extension on success
	 */
	protected function validateAvatarUpload(UploadedFile $file)
	{
		$cfg = ModuleConfig::snapshot();
		$allowedExtensions = array_map('strtolower', (array) $cfg['avatarAllowedExtensions']);
		$maxSize = (int) $cfg['avatarMaxSize'];

		if ((int) $file->error !== UPLOAD_ERR_OK) {
			$this->avatarUploadError = Yii::t('userextended', 'Avatar upload failed.');
			return false;
		}

		if ($file->size <= 0 || ($maxSize > 0 && $file->size > $maxSize)) {
			$this->avatarUploadError = Yii::t('userextended', 'Avatar exceeds the maximum allowed size.');
			return false;
		}

		$originalName = (string) $file->name;
		$baseName = pathinfo($originalName, PATHINFO_FILENAME);
		if ($baseName === '' || strpos($baseName, '.') !== false) {
			$this->avatarUploadError = Yii::t('userextended', 'Invalid avatar file name.');
			return false;
		}

		$extension = strtolower((string) $file->extension);
		if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
			$this->avatarUploadError = Yii::t('userextended', 'Avatar must be a JPG, PNG or WEBP image.');
			return false;
		}

		$blocked = ['php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'php8', 'cgi', 'pl', 'py', 'asp', 'aspx', 'exe', 'sh', 'bat', 'cmd', 'com', 'js', 'html', 'htm', 'shtml', 'svg'];
		foreach ($blocked as $blockedExt) {
			if (stripos($originalName, '.' . $blockedExt . '.') !== false || preg_match('/\.' . preg_quote($blockedExt, '/') . '$/i', $originalName)) {
				$this->avatarUploadError = Yii::t('userextended', 'Invalid avatar file.');
				return false;
			}
		}

		$mime = $this->detectAvatarMimeType($file);
		if ($mime === null || !isset(self::$avatarMimeMap[$mime])) {
			$this->avatarUploadError = Yii::t('userextended', 'Avatar must be a JPG, PNG or WEBP image.');
			return false;
		}

		$canonicalExt = self::$avatarMimeMap[$mime];
		$aliases = self::$avatarExtensionAliases[$canonicalExt];
		if (!in_array($extension, $aliases, true)) {
			$this->avatarUploadError = Yii::t('userextended', 'Avatar must be a JPG, PNG or WEBP image.');
			return false;
		}

		$canonicalAllowed = in_array($canonicalExt, $allowedExtensions, true)
			|| ($canonicalExt === 'jpg' && in_array('jpeg', $allowedExtensions, true));
		if (!$canonicalAllowed) {
			$this->avatarUploadError = Yii::t('userextended', 'Avatar must be a JPG, PNG or WEBP image.');
			return false;
		}

		$imageInfo = @getimagesize($file->tempName);
		if ($imageInfo === false || empty($imageInfo['mime']) || $imageInfo['mime'] !== $mime) {
			$this->avatarUploadError = Yii::t('userextended', 'Avatar must be a JPG, PNG or WEBP image.');
			return false;
		}

		return $canonicalExt;
	}

	/**
	 * @param UploadedFile $file
	 *
	 * @return string|null
	 */
	protected function detectAvatarMimeType(UploadedFile $file)
	{
		$tempName = $file->tempName;
		if (!is_string($tempName) || $tempName === '' || !is_uploaded_file($tempName)) {
			return null;
		}

		if (class_exists(\finfo::class)) {
			$finfo = new \finfo(FILEINFO_MIME_TYPE);
			$mime = $finfo->file($tempName);
			if (is_string($mime) && $mime !== '') {
				return $mime;
			}
		}

		if (function_exists('mime_content_type')) {
			$mime = mime_content_type($tempName);
			if (is_string($mime) && $mime !== '') {
				return $mime;
			}
		}

		return null;
	}

	/**
	 * Resolve and optionally ensure the avatar storage directory exists.
	 *
	 * @param string $filePath
	 * @param bool $create
	 *
	 * @return string|false Absolute real path
	 */
	protected function resolveAvatarDirectory($filePath, $create = false)
	{
		$path = $filePath !== '' && $filePath !== null
			? $filePath
			: Yii::getAlias(ModuleConfig::get('avatarPath'));

		$path = Yii::getAlias($path);
		$path = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);

		if (!is_dir($path)) {
			if (!$create || !FileHelper::createDirectory($path)) {
				return false;
			}
		}

		$realPath = realpath($path);
		if ($realPath === false || !is_dir($realPath)) {
			return false;
		}

		if ($create && !is_writable($realPath)) {
			return false;
		}

		return $realPath;
	}

	/**
	 * @param string $directory Absolute directory path
	 * @param string $path Absolute file path
	 *
	 * @return bool
	 */
	protected function isPathInsideDirectory($directory, $path)
	{
		$directory = rtrim(str_replace('\\', '/', $directory), '/') . '/';
		$path = str_replace('\\', '/', $path);

		return strpos($path, $directory) === 0
			&& strpos($path, '..') === false;
	}

	/**
	 * fetch stored image file name with complete path
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
    public function getImagePath()
    {
	    if (!$this->avatar || !$this->isSafeAvatarBasename($this->avatar)) {
		    return null;
	    }

	    $basePath = $this->resolveAvatarDirectory(Yii::getAlias(ModuleConfig::get('avatarPath')));
	    if ($basePath === false) {
		    return null;
	    }

	    $fullPath = $basePath . DIRECTORY_SEPARATOR . basename($this->avatar);
	    return $this->isPathInsideDirectory($basePath, $fullPath) ? $fullPath : null;
    }

	/**
	 * fetch stored image url
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
    public function getImageUrl()
    {
	    if ( !$this->avatar && $this->getAccountAttributes() !== null )
        {
            $imageURL = $this->getSocialImage();

        } else {

            $avatar = ($this->avatar && $this->isSafeAvatarBasename($this->avatar))
	            ? basename($this->avatar)
	            : 'default.png';
            $imageURL = Yii::getAlias(ModuleConfig::get('avatarURL')).$avatar;
        }

        return $imageURL;
    }

	/**
	 * Process deletion of image
	 *
	 * @param string $avatarOld
	 *
	 * @return bool
	 * @throws InvalidArgumentException
	 */
    public function deleteImage($avatarOld)
    {
	    if ($avatarOld === null || $avatarOld === '' || $avatarOld === 'default.png') {
		    return false;
	    }

	    $avatarOld = basename((string) $avatarOld);
	    if (!$this->isSafeAvatarBasename($avatarOld)) {
		    return false;
	    }

	    $basePath = $this->resolveAvatarDirectory(Yii::getAlias(ModuleConfig::get('avatarPath')));
	    if ($basePath === false) {
		    return false;
	    }

	    $avatarURL = $basePath . DIRECTORY_SEPARATOR . $avatarOld;
	    if (!$this->isPathInsideDirectory($basePath, $avatarURL)) {
		    return false;
	    }

	    $realFile = realpath($avatarURL);
	    if ($realFile === false || !is_file($realFile) || !$this->isPathInsideDirectory($basePath, $realFile)) {
		    return false;
	    }

        // check if uploaded file can be deleted on server
        if (!unlink($realFile)) {
            return false;
        }

        // if deletion successful and this was the current avatar, reset attribute
        if ($this->avatar !== null && basename((string) $this->avatar) === $avatarOld) {
            $this->avatar = null;
        }

        return true;
    }

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	protected function isSafeAvatarBasename($name)
	{
		return (bool) preg_match('/^[A-Za-z0-9_-]+\.(jpg|jpeg|png|webp)$/', $name);
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

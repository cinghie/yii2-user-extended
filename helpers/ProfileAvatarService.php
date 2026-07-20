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

namespace cinghie\userextended\helpers;

use Yii;
use cinghie\userextended\models\Profile;
use yii\web\UploadedFile;

/**
 * Shared avatar capture / apply / cleanup for profile update flows.
 *
 * Call {@see begin()} after AJAX validation and before model->load() so the
 * uploaded file is read once; then {@see applyAfterLoad()} / {@see finalizeAfterSave()}.
 * On failed save call {@see rollbackFailedSave()} to remove an orphaned file.
 */
final class ProfileAvatarService
{
	private Profile $profile;

	private ?string $oldImage;

	private UploadedFile|false $image = false;

	private function __construct(Profile $profile)
	{
		$this->profile = $profile;
		$current = $profile->avatar;
		$this->oldImage = is_string($current) && $current !== '' ? $current : null;

		if (ModuleConfig::get('avatar')) {
			$avatarPath = Yii::getAlias((string) ModuleConfig::get('avatarPath'));
			$uploaded = $profile->uploadAvatar($avatarPath);
			$this->image = $uploaded instanceof UploadedFile ? $uploaded : false;
		}
	}

	/**
	 * Capture current avatar + optional upload before ActiveForm load() clears the file input.
	 */
	public static function begin(Profile $profile): self
	{
		return new self($profile);
	}

	/**
	 * Restore avatar attribute after load() (file inputs wipe the attribute).
	 */
	public function applyAfterLoad(): void
	{
		if ($this->image === false) {
			$this->profile->avatar = $this->oldImage;
		} else {
			$this->profile->avatar = $this->image->name;
		}
	}

	/**
	 * After a successful save, remove the previous file and re-persist the new basename.
	 */
	public function finalizeAfterSave(): void
	{
		if ($this->image === false || $this->oldImage === null || $this->oldImage === $this->image->name) {
			return;
		}

		$this->profile->deleteImage($this->oldImage);
		// deleteImage() may null the current avatar; keep the new one
		$this->profile->updateAttributes(['avatar' => $this->image->name]);
	}

	/**
	 * If the model failed to save after a successful disk upload, delete the new file
	 * and restore the previous avatar attribute for re-display.
	 */
	public function rollbackFailedSave(): void
	{
		if ($this->image === false) {
			return;
		}

		$newName = $this->image->name;
		$this->profile->deleteImage($newName);
		$this->profile->avatar = $this->oldImage;
		$this->image = false;
	}
}

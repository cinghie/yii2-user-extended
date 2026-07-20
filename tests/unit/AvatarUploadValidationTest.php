<?php

namespace cinghie\userextended\tests\unit;

use ReflectionMethod;
use cinghie\userextended\models\Profile;
use cinghie\userextended\tests\TestCase;
use yii\web\UploadedFile;

class AvatarUploadValidationTest extends TestCase
{
	/** @var string[] */
	private array $tempFiles = [];

	protected function setUp(): void
	{
		parent::setUp();
		$this->mockApplication([
			'avatar' => true,
			'avatarAllowedExtensions' => ['jpg', 'jpeg', 'png', 'webp'],
			'avatarMaxSize' => 2048,
		]);
	}

	protected function tearDown(): void
	{
		foreach ($this->tempFiles as $file) {
			if (is_string($file) && is_file($file)) {
				@unlink($file);
			}
		}
		parent::tearDown();
	}

	public function testRejectsDoubleExtension(): void
	{
		$profile = new Profile();
		$file = new UploadedFile([
			'name' => 'shell.php.jpg',
			'tempName' => $this->createTempImage(),
			'type' => 'image/jpeg',
			'size' => 100,
			'error' => UPLOAD_ERR_OK,
		]);

		$method = new ReflectionMethod(Profile::class, 'validateAvatarUpload');
		$method->setAccessible(true);
		$result = $method->invoke($profile, $file);
		$this->assertFalse($result);
	}

	public function testRejectsOversizedFile(): void
	{
		$profile = new Profile();
		$file = new UploadedFile([
			'name' => 'big.jpg',
			'tempName' => $this->createTempImage(),
			'type' => 'image/jpeg',
			'size' => 999999,
			'error' => UPLOAD_ERR_OK,
		]);

		$method = new ReflectionMethod(Profile::class, 'validateAvatarUpload');
		$method->setAccessible(true);
		$result = $method->invoke($profile, $file);
		$this->assertFalse($result);
	}

	private function createTempImage(): string
	{
		$path = tempnam(sys_get_temp_dir(), 'avatar');
		$this->tempFiles[] = $path;
		$data = base64_decode(
			'/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBEQCEAwEPwAB//9k='
		);
		file_put_contents($path, $data);

		return $path;
	}
}

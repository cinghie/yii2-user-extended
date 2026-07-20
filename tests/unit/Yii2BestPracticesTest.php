<?php

namespace cinghie\userextended\tests\unit;

use ReflectionClass;
use Yii;
use cinghie\userextended\Bootstrap;
use cinghie\userextended\Module;
use cinghie\userextended\assets\SessionExpireAsset;
use cinghie\userextended\controllers\AdminController;
use cinghie\userextended\controllers\AssignmentController;
use cinghie\userextended\controllers\PermissionController;
use cinghie\userextended\controllers\RoleController;
use cinghie\userextended\controllers\SecurityController;
use cinghie\userextended\filters\BackendFilter;
use cinghie\userextended\filters\PasswordExpireFilter;
use cinghie\userextended\helpers\SafeHtml;
use cinghie\userextended\models\Assignment;
use cinghie\userextended\models\UserSearch;
use cinghie\userextended\tests\TestCase;
use dektrium\user\Finder;
use yii\base\ActionFilter;
use yii\base\BootstrapInterface;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\AssetBundle;
use yii\web\Controller;

/**
 * Assert Yii2 recommended patterns used by this package (structure / security filters / assets / i18n).
 *
 * @see https://www.yiiframework.com/doc/guide/2.0/en/structure-controllers
 * @see https://www.yiiframework.com/doc/guide/2.0/en/security-best-practices
 * @see https://www.yiiframework.com/doc/guide/2.0/en/structure-assets
 */
class Yii2BestPracticesTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->mockApplication([
			'enableSecurityAudit' => false,
		], [
			'modules' => [
				'user' => [
					'class' => \dektrium\user\Module::class,
					'enableRegistration' => false,
				],
			],
		]);
	}

	public function testBootstrapImplementsBootstrapInterface(): void
	{
		$this->assertTrue(is_subclass_of(Bootstrap::class, BootstrapInterface::class));
		$this->assertTrue(method_exists(Bootstrap::class, 'bootstrap'));
	}

	public function testModuleRegistersPhpMessageSource(): void
	{
		$module = Yii::$app->getModule('userextended');
		$this->assertInstanceOf(Module::class, $module);
		$this->assertArrayHasKey('userextended', Yii::$app->i18n->translations);
		$t = Yii::$app->i18n->translations['userextended'];
		if (is_array($t)) {
			$this->assertSame(\yii\i18n\PhpMessageSource::class, $t['class']);
			$this->assertSame('en', $t['sourceLanguage'] ?? 'en');
		}
	}

	public function testControllersExtendYiiWebController(): void
	{
		foreach ([
			AdminController::class,
			SecurityController::class,
			AssignmentController::class,
			RoleController::class,
			PermissionController::class,
		] as $class) {
			$this->assertTrue(is_subclass_of($class, Controller::class), $class . ' must extend yii\\web\\Controller');
		}
	}

	public function testAdminUsesAccessControlAndVerbFilter(): void
	{
		$finder = Yii::createObject(Finder::class);
		$controller = new AdminController('admin', Yii::$app->getModule('user'), $finder);
		$behaviors = $controller->behaviors();

		$this->assertArrayHasKey('access', $behaviors);
		$this->assertSame(AccessControl::class, $behaviors['access']['class']);
		$this->assertArrayHasKey('verbs', $behaviors);
		$this->assertSame(VerbFilter::class, $behaviors['verbs']['class']);

		$verbs = $behaviors['verbs']['actions'];
		foreach (['delete', 'block', 'confirm', 'deletemultiple', 'activemultiple', 'deactivemultiple', 'resend-password'] as $action) {
			$this->assertArrayHasKey($action, $verbs, "Missing VerbFilter for $action");
			$this->assertContains('POST', array_map('strtoupper', (array) $verbs[$action]));
		}

		// Impersonation denied at AccessControl level
		$switchDenied = false;
		foreach ($behaviors['access']['rules'] as $rule) {
			if (($rule['allow'] ?? null) === false && in_array('switch', (array) ($rule['actions'] ?? []), true)) {
				$switchDenied = true;
				break;
			}
		}
		$this->assertTrue($switchDenied, 'AccessControl must deny switch action');
	}

	public function testSecuritySessionPingUsesAccessAndVerbs(): void
	{
		$finder = Yii::createObject(Finder::class);
		$controller = new SecurityController('security', Yii::$app->getModule('user'), $finder);
		$behaviors = $controller->behaviors();

		$this->assertArrayHasKey('access', $behaviors);
		$this->assertArrayHasKey('verbs', $behaviors);
		$this->assertContains('get', array_map('strtolower', (array) $behaviors['verbs']['actions']['session-ping']));
	}

	public function testControllersEnableCsrfByDefault(): void
	{
		$finder = Yii::createObject(Finder::class);
		$admin = new AdminController('admin', Yii::$app->getModule('user'), $finder);
		$this->assertTrue($admin->enableCsrfValidation, 'Yii controllers should keep CSRF on');
	}

	public function testAssignmentOnlyMassAssignsItems(): void
	{
		// Avoid Assignment::init() (needs authManager); test safeAttributes contract only
		$ref = new ReflectionClass(Assignment::class);
		$model = $ref->newInstanceWithoutConstructor();
		$model->user_id = 42;
		$model->items = [];

		$this->assertSame(['items'], $model->safeAttributes());
		$this->assertNotContains('user_id', $model->safeAttributes());

		$model->setAttributes([
			'user_id' => 999,
			'items' => ['admin'],
		], true);

		$this->assertSame(42, (int) $model->user_id, 'user_id must not be mass-assignable');
		$this->assertSame(['admin'], $model->items);
	}

	public function testFiltersExtendActionFilter(): void
	{
		$this->assertTrue(is_subclass_of(BackendFilter::class, ActionFilter::class));
		$this->assertTrue(is_subclass_of(PasswordExpireFilter::class, ActionFilter::class));
	}

	public function testSessionExpireAssetFollowsYiiAssetBundleGuide(): void
	{
		$bundle = new SessionExpireAsset();
		$bundle->init();

		$this->assertInstanceOf(AssetBundle::class, $bundle);
		$this->assertNotEmpty($bundle->sourcePath, 'Package assets should use sourcePath (published), not a fixed baseUrl');
		$this->assertDirectoryExists($bundle->sourcePath);
		$this->assertStringContainsString('static', str_replace('\\', '/', $bundle->sourcePath));
		$this->assertContains(\yii\web\YiiAsset::class, $bundle->depends);

		$js = $bundle->js[0];
		$this->assertIsArray($js);
		$this->assertTrue($js['appendTimestamp'] ?? false, 'appendTimestamp recommended for cache busting');
		$this->assertFileExists($bundle->sourcePath . DIRECTORY_SEPARATOR . $js[0]);
	}

	public function testUserSearchRuleFilterUsesParamBindingNotConcatSql(): void
	{
		$src = file_get_contents((new ReflectionClass(UserSearch::class))->getFileName());
		$this->assertStringNotContainsString("item_name = '\" .", $src);
		$this->assertStringNotContainsString('item_name = "' . " .", $src);
		$this->assertMatchesRegularExpression("/andWhere\\(\\s*\\[\\s*'aa\\.item_name'\\s*=>\\s*\\\$this->rule/", $src);
	}

	public function testSafeHtmlEncodesLikeHtmlEncode(): void
	{
		$raw = '<script>alert(1)</script>';
		$this->assertSame(\yii\helpers\Html::encode($raw), SafeHtml::encode($raw));
	}

	public function testRolePermissionDeleteArePostOnly(): void
	{
		foreach ([RoleController::class, PermissionController::class] as $class) {
			$controller = new $class('x', Yii::$app->getModule('userextended'));
			$behaviors = $controller->behaviors();
			$this->assertArrayHasKey('verbs', $behaviors, $class);
			$this->assertSame(VerbFilter::class, $behaviors['verbs']['class']);
			$this->assertContains('POST', array_map('strtoupper', (array) $behaviors['verbs']['actions']['delete']));
		}
	}
}

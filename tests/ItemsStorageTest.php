<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Files\FileHelper;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Tests\Common\ItemsStorageTestTrait;

final class ItemsStorageTest extends TestCase
{
    use StorageFilePathTrait;
    use ItemsStorageTestTrait {
        setUp as protected traitSetUp;
    }

    protected function setUp(): void
    {
        if ($this->name() === 'testFailCreateDirectory' || $this->name() === 'testCreateNestedDirectory') {
            FileHelper::ensureDirectory($this->getTempDirectory());
            FileHelper::clearDirectory($this->getTempDirectory());
        }

        $this->traitSetUp();
    }

    protected function tearDown(): void
    {
        if ($this->name() === 'testFailCreateDirectory' || $this->name() === 'testCreateNestedDirectory') {
            FileHelper::removeDirectory($this->getTempDirectory());
        }

        $this->clearFixturesFiles();
    }

    public function testFailCreateDirectory(): void
    {
        $directory = $this->getTempDirectory() . '/file.txt';
        touch($directory);

        $storage = new ItemsStorage($directory);
        $permission = new Permission('createPost');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create directory "' . $directory . '". mkdir(): File exists');
        $storage->add($permission);
    }

    public function testCreateNestedDirectory(): void
    {
        $directory = $this->getTempDirectory() . '/test/create/nested/directory';

        $storage = new ItemsStorage($directory);
        $storage->add(new Permission('createPost'));

        $this->assertFileExists($directory . '/items.php');
    }

    public function testGetAllWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertEmpty($testStorage->getAll());
    }

    public function testGetByNamesWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertEmpty($testStorage->getByNames(['posts.view']));
    }

    public function testGetWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertNull($testStorage->get('posts.view'));
    }

    public function testExistsWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertFalse($testStorage->exists('posts.view'));
    }

    public function testRoleExistsWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertFalse($testStorage->roleExists('posts.viewer'));
    }

    public function testGetRolesWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertEmpty($testStorage->getRoles());
    }

    public function testGetRolesByNamesWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertEmpty($testStorage->getRolesByNames(['posts.viewer']));
    }

    public function testGetRoleWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertEmpty($testStorage->getRole('posts.viewer'));
    }

    public function testGetPermissionsWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertEmpty($testStorage->getPermissions());
    }

    public function testGetPermissionsByNamesWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertEmpty($testStorage->getPermissionsByNames(['posts.view']));
    }

    public function testGetPermissionWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertEmpty($testStorage->getPermission('posts.view'));
    }

    public function testGetParentsWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertEmpty($testStorage->getParents('posts.view'));
    }

    public function testGetAccessTreeWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base item not found.');
        $testStorage->getAccessTree('posts.view');
    }

    public function testGetDirectChildrenWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertEmpty($testStorage->getDirectChildren('posts.viewer'));
    }

    public function testGetAllChildrenWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertEmpty($testStorage->getAllChildren('posts.viewer'));
    }

    public function testGetAllChildRolesWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertEmpty($testStorage->getAllChildRoles('posts.redactor'));
    }

    public function testGetAllChildPermissionsWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertEmpty($testStorage->getAllChildPermissions('posts.viewer'));
    }

    public function testHasChildrenWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertFalse($testStorage->hasChildren('posts.viewer'));
    }

    public function testHasChildWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertFalse($testStorage->hasChild('posts.viewer', 'posts.view'));
    }

    public function testHasDirectChildWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertFalse($testStorage->hasDirectChild('posts.viewer', 'posts.view'));
    }

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage($this->getDataPath(), enableConcurrencyHandling: true);
    }

    protected function getItemsStorageForModificationAssertions(): ItemsStorageInterface
    {
        return $this->createItemsStorage();
    }

    private function getTempDirectory(): string
    {
        return __DIR__ . '/temp';
    }
}

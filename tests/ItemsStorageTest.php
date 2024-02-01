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
    use ItemsStorageTestTrait {
        setUp as protected traitSetUp;
    }
    use StorageFilePathTrait;

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
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getAll());
    }

    public function testGetByNamesWithConcurrency(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getByNames(['posts.view']));
    }

    public function testGetWithConcurrency(): void
    {
        $this->assertNull($this->getEmptyConcurrentItemsStorage()->get('posts.view'));
    }

    public function testExistsWithConcurrency(): void
    {
        $this->assertFalse($this->getEmptyConcurrentItemsStorage()->exists('posts.view'));
    }

    public function testRoleExistsWithConcurrency(): void
    {
        $this->assertFalse($this->getEmptyConcurrentItemsStorage()->roleExists('posts.viewer'));
    }

    public function testGetRolesWithConcurrency(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getRoles());
    }

    public function testGetRolesByNamesWithConcurrency(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getRolesByNames(['posts.viewer']));
    }

    public function testGetRoleWithConcurrency(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getRole('posts.viewer'));
    }

    public function testGetPermissionsWithConcurrency(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getPermissions());
    }

    public function testGetPermissionsByNamesWithConcurrency(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getPermissionsByNames(['posts.view']));
    }

    public function testGetPermissionWithConcurrency(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getPermission('posts.view'));
    }

    public function testGetParentsWithConcurrency(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getParents('posts.view'));
    }

    public function testGetAccessTreeWithConcurrency(): void
    {
        $this->markTestSkipped();

        $storage = $this->getEmptyConcurrentItemsStorage();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base item not found.');
        $storage->getAccessTree('posts.view');
    }

    public function testGetDirectChildrenWithConcurrency(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getDirectChildren('posts.viewer'));
    }

    public function testGetAllChildrenWithConcurrency(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getAllChildren('posts.viewer'));
    }

    public function testGetAllChildRolesWithConcurrency(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getAllChildRoles('posts.redactor'));
    }

    public function testGetAllChildPermissionsWithConcurrency(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getAllChildPermissions('posts.viewer'));
    }

    public function testHasChildrenWithConcurrency(): void
    {
        $this->assertFalse($this->getEmptyConcurrentItemsStorage()->hasChildren('posts.viewer'));
    }

    public function testHasChildWithConcurrency(): void
    {
        $this->assertFalse($this->getEmptyConcurrentItemsStorage()->hasChild('posts.viewer', 'posts.view'));
    }

    public function testHasDirectChildWithConcurrency(): void
    {
        $this->assertFalse($this->getEmptyConcurrentItemsStorage()->hasDirectChild('posts.viewer', 'posts.view'));
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

    private function getEmptyConcurrentItemsStorage(): ItemsStorageInterface
    {
        $storage = $this->getItemsStorageForModificationAssertions();
        $this->getItemsStorage()->clear();

        return $storage;
    }
}

<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Files\FileHelper;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Role;
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
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getAll());
    }

    public function testGetByNamesWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getByNames(['posts.view']));
    }

    public function testGetWithConcurrency(): void
    {
        $this->assertNotNull($this->getEmptyConcurrentItemsStorage()->get('posts.view'));
    }

    public function testExistsWithConcurrency(): void
    {
        $this->assertTrue($this->getEmptyConcurrentItemsStorage()->exists('posts.view'));
    }

    public function testRoleExistsWithConcurrency(): void
    {
        $this->assertTrue($this->getEmptyConcurrentItemsStorage()->roleExists('posts.viewer'));
    }

    public function testAddWithConcurrency(): void
    {
        $testStorage = new ItemsStorage($this->getDataPath());
        $actionStorage = $this->getItemsStorage();

        $actionStorage->add(new Permission('test1'));
        $count = count($actionStorage->getAll());
        $actionStorage->add(new Permission('test2'));

        $testStorage->add(new Permission('test1'));
        $this->assertCount($count, $testStorage->getAll());
    }

    public function testUpdateWithConcurrency(): void
    {
        $testStorage = new ItemsStorage($this->getDataPath());
        $actionStorage = $this->getItemsStorage();

        $commonUpdatedItem = $actionStorage->get('posts.view')->withName('posts.view1');
        $actionStorage->update('posts.view', $commonUpdatedItem);
        $actionStorage->update('posts.create', $actionStorage->get('posts.create')->withName('posts.create1'));

        $testStorage->update('posts.view', $commonUpdatedItem);
        $this->assertTrue($testStorage->exists('posts.view1'));
        $this->assertFalse($testStorage->exists('posts.create1'));
    }

    public function testRemoveWithConcurrency(): void
    {
        $testStorage = new ItemsStorage($this->getDataPath());
        $actionStorage = $this->getItemsStorage();

        $actionStorage->remove('posts.view');
        $count = count($actionStorage->getAll());
        $actionStorage->remove('posts.create');

        $testStorage->remove('posts.view');
        $this->assertCount($count, $testStorage->getAll());
    }

    public function testGetRolesWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getRoles());
    }

    public function testGetRolesByNamesWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getRolesByNames(['posts.viewer']));
    }

    public function testGetRoleWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getRole('posts.viewer'));
    }

    public function testClearRolesWithConcurrency(): void
    {
        $testStorage = new ItemsStorage($this->getDataPath());
        $actionStorage = $this->getItemsStorage();

        $permissionsCount = count($actionStorage->getPermissions());
        $actionStorage->add(new Permission('test1'));

        $testStorage->clearRoles();

        $all = $testStorage->getAll();
        $this->assertCount($permissionsCount, $all);
        $this->assertContainsOnlyInstancesOf(Permission::class, $all);
    }

    public function testGetPermissionsWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getPermissions());
    }

    public function testGetPermissionsByNamesWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getPermissionsByNames(['posts.view']));
    }

    public function testGetPermissionWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getPermission('posts.view'));
    }

    public function testClearPermissionsWithConcurrency(): void
    {
        $testStorage = new ItemsStorage($this->getDataPath());
        $actionStorage = $this->getItemsStorage();

        $rolesCount = count($actionStorage->getRoles());
        $actionStorage->add(new Role('test1'));

        $testStorage->clearPermissions();

        $all = $testStorage->getAll();
        $this->assertCount($rolesCount, $all);
        $this->assertContainsOnlyInstancesOf(Role::class, $all);
    }

    public function testGetParentsWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getParents('posts.view'));
    }

    public function testGetAccessTreeWithConcurrency(): void
    {
        $this->markTestSkipped();

        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getAccessTree('posts.view'));
    }

    public function testGetDirectChildrenWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getDirectChildren('posts.viewer'));
    }

    public function testGetAllChildrenWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getAllChildren('posts.viewer'));
    }

    public function testGetAllChildRolesWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getAllChildRoles('posts.redactor'));
    }

    public function testGetAllChildPermissionsWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getAllChildPermissions('posts.viewer'));
    }

    public function testHasChildrenWithConcurrency(): void
    {
        $this->assertTrue($this->getEmptyConcurrentItemsStorage()->hasChildren('posts.viewer'));
    }

    public function testHasChildWithConcurrency(): void
    {
        $this->assertTrue($this->getEmptyConcurrentItemsStorage()->hasChild('posts.viewer', 'posts.view'));
    }

    public function testHasDirectChildWithConcurrency(): void
    {
        $this->assertTrue($this->getEmptyConcurrentItemsStorage()->hasDirectChild('posts.viewer', 'posts.view'));
    }

    public function testAddChildWithConcurrency(): void
    {
        $testStorage = new ItemsStorage($this->getDataPath());
        $actionStorage = $this->getItemsStorage();

        $actionStorage->addChild('posts.viewer', 'posts.create');
        $actionStorage->addChild('posts.viewer', 'posts.update');

        $testStorage->addChild('posts.viewer', 'posts.create');
        $this->assertTrue($testStorage->hasChild('posts.viewer', 'posts.create'));
        $this->assertFalse($testStorage->hasChild('posts.viewer', 'posts.update'));
    }

    public function testMultipleCallsWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $count = count($actionStorage->getAll());

        $actionStorage->add(new Permission('test1'));
        $this->assertCount($count, $testStorage->getAll());

        $actionStorage->add(new Permission('test2'));
        $this->assertCount($count, $testStorage->getAll());
    }

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage($this->getDataPath());
    }

    protected function getItemsStorageForModificationAssertions(): ItemsStorageInterface
    {
        return str_ends_with($this->name(), 'WithConcurrency') ? $this->createItemsStorage() : $this->getItemsStorage();
    }

    private function getEmptyConcurrentItemsStorage(): ItemsStorageInterface
    {
        $storage = $this->getItemsStorageForModificationAssertions();
        $this->getItemsStorage()->clear();

        return $storage;
    }

    private function getTempDirectory(): string
    {
        return __DIR__ . '/temp';
    }
}

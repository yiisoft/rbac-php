<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Files\FileHelper;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Php\ConcurrentItemsStorageDecorator;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Role;
use Yiisoft\Rbac\Tests\Common\ItemsStorageTestTrait;

final class ItemsStorageWithConcurrencyHandlingTest extends TestCase
{
    use ItemsStorageTestTrait {
        setUp as protected traitSetUp;
    }
    use StorageFilePathTrait;

    protected function setUp(): void
    {
        $this->traitSetUp();
    }

    protected function tearDown(): void
    {
        $this->clearFixturesFiles();
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

    public function testAddWithConcurrency(): void
    {
        $innerTestStorage = new ItemsStorage($this->getDataPath());
        $testStorage = new ConcurrentItemsStorageDecorator($innerTestStorage);
        $actionStorage = $this->getItemsStorage();

        $actionStorage->add(new Permission('test1'));
        $actionStorage->add(new Permission('test2'));
        $count = count($actionStorage->getAll());

        $testStorage->add(new Permission('test1'));
        $this->assertCount($count, $innerTestStorage->getAll());
    }

    public function testUpdateWithConcurrency(): void
    {
        $innerTestStorage = new ItemsStorage($this->getDataPath());
        $testStorage = new ConcurrentItemsStorageDecorator($innerTestStorage);
        $actionStorage = $this->getItemsStorage();

        $commonUpdatedItem = $actionStorage->get('posts.view')->withName('posts.view1');
        $actionStorage->update('posts.view', $commonUpdatedItem);
        $actionStorage->update('posts.create', $actionStorage->get('posts.create')->withName('posts.create1'));

        $testStorage->update('posts.view', $commonUpdatedItem);
        $this->assertTrue($innerTestStorage->exists('posts.view1'));
        $this->assertTrue($innerTestStorage->exists('posts.create1'));
    }

    public function testRemoveWithConcurrency(): void
    {
        $innerTestStorage = new ItemsStorage($this->getDataPath());
        $testStorage = new ConcurrentItemsStorageDecorator($innerTestStorage);
        $actionStorage = $this->getItemsStorage();

        $count = count($actionStorage->getAll());
        $actionStorage->remove('posts.view');
        $actionStorage->remove('posts.create');

        $testStorage->remove('posts.view');
        $this->assertCount($count - 2, $innerTestStorage->getAll());
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

    public function testClearRolesWithConcurrency(): void
    {
        $innerTestStorage = new ItemsStorage($this->getDataPath());
        $testStorage = new ConcurrentItemsStorageDecorator($innerTestStorage);
        $actionStorage = $this->getItemsStorage();

        $actionStorage->add(new Permission('test1'));
        $permissionsCount = count($actionStorage->getPermissions());

        $testStorage->clearRoles();

        $all = $innerTestStorage->getAll();
        $this->assertCount($permissionsCount, $all);
        $this->assertContainsOnlyInstancesOf(Permission::class, $all);
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

    public function testClearPermissionsWithConcurrency(): void
    {
        $innerTestStorage = new ItemsStorage($this->getDataPath());
        $testStorage = new ConcurrentItemsStorageDecorator($innerTestStorage);
        $actionStorage = $this->getItemsStorage();

        $actionStorage->add(new Role('test1'));
        $rolesCount = count($actionStorage->getRoles());

        $testStorage->clearPermissions();

        $all = $innerTestStorage->getAll();
        $this->assertCount($rolesCount, $all);
        $this->assertContainsOnlyInstancesOf(Role::class, $all);
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

    public function testAddChildWithConcurrency(): void
    {
        $innerTestStorage = new ItemsStorage($this->getDataPath());
        $testStorage = new ConcurrentItemsStorageDecorator($innerTestStorage);
        $actionStorage = $this->getItemsStorage();

        $actionStorage->addChild('posts.viewer', 'posts.create');
        $actionStorage->addChild('posts.viewer', 'posts.update');

        $testStorage->addChild('posts.viewer', 'posts.create');
        $this->assertTrue($innerTestStorage->hasChild('posts.viewer', 'posts.create'));
        $this->assertTrue($innerTestStorage->hasChild('posts.viewer', 'posts.update'));
    }

    public function testMultipleCallsWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $count = count($actionStorage->getAll());

        $actionStorage->add(new Permission('test1'));
        $this->assertCount($count + 1, $testStorage->getAll());

        $actionStorage->add(new Permission('test2'));
        $this->assertCount($count + 2, $testStorage->getAll());
    }

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ConcurrentItemsStorageDecorator(new ItemsStorage($this->getDataPath()));
    }

    protected function getItemsStorageForModificationAssertions(): ItemsStorageInterface
    {
        return $this->createItemsStorage();
    }

    private function getEmptyConcurrentItemsStorage(): ItemsStorageInterface
    {
        $storage = $this->getItemsStorageForModificationAssertions();
        $this->getItemsStorage()->clear();

        return $storage;
    }
}

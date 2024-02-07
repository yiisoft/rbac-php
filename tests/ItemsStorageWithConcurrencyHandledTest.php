<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Php\ConcurrentItemsStorageDecorator;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Role;
use Yiisoft\Rbac\Tests\Common\ItemsStorageTestTrait;

final class ItemsStorageWithConcurrencyHandledTest extends TestCase
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

    public function testGetAll(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getAll());
    }

    public function testGetByNames(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getByNames(['posts.view']));
    }

    public function testGet(): void
    {
        $this->assertNull($this->getEmptyConcurrentItemsStorage()->get('posts.view'));
    }

    public function testExists(): void
    {
        $this->assertFalse($this->getEmptyConcurrentItemsStorage()->exists('posts.view'));
    }

    public function testRoleExists(): void
    {
        $this->assertFalse($this->getEmptyConcurrentItemsStorage()->roleExists('posts.viewer'));
    }

    public function testAddWithCurrentTimestamps(): void
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

    public function testUpdate(): void
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

    public function testRemove(): void
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

    public function testGetRoles(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getRoles());
    }

    public function testGetRolesByNames(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getRolesByNames(['posts.viewer']));
    }

    public function testGetRole(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getRole('posts.viewer'));
    }

    public function testClearRoles(): void
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

    public function testGetPermissions(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getPermissions());
    }

    public function testGetPermissionsByNames(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getPermissionsByNames(['posts.view']));
    }

    public function testGetPermission(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getPermission('posts.view'));
    }

    public function testClearPermissions(): void
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

    public function testGetParents(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getParents('posts.view'));
    }

    public function testGetAccessTree(): void
    {
        $this->markTestSkipped();

        $storage = $this->getEmptyConcurrentItemsStorage();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base item not found.');
        $storage->getAccessTree('posts.view');
    }

    public function testGetDirectChildren(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getDirectChildren('posts.viewer'));
    }

    public function testGetAllChildren(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getAllChildren('posts.viewer'));
    }

    public function testGetAllChildRoles(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getAllChildRoles('posts.redactor'));
    }

    public function testGetAllChildPermissions(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentItemsStorage()->getAllChildPermissions('posts.viewer'));
    }

    public function testHasChildren(): void
    {
        $this->assertFalse($this->getEmptyConcurrentItemsStorage()->hasChildren('posts.viewer'));
    }

    public function testHasChild(): void
    {
        $this->assertFalse($this->getEmptyConcurrentItemsStorage()->hasChild('posts.viewer', 'posts.view'));
    }

    public function testHasDirectChild(): void
    {
        $this->assertFalse($this->getEmptyConcurrentItemsStorage()->hasDirectChild('posts.viewer', 'posts.view'));
    }

    public function testAddChild(): void
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

    public function testRemoveChild(): void
    {
        $innerTestStorage = new ItemsStorage($this->getDataPath());
        $testStorage = new ConcurrentItemsStorageDecorator($innerTestStorage);
        $actionStorage = $this->getItemsStorage();

        $actionStorage->removeChild(parentName: 'posts.redactor', childName: 'posts.create');
        $actionStorage->removeChild(parentName: 'posts.redactor', childName: 'posts.update');

        $testStorage->removeChild(parentName: 'posts.redactor', childName: 'posts.create');
        $this->assertFalse($innerTestStorage->hasChild(parentName: 'posts.redactor', childName: 'posts.create'));
        $this->assertFalse($innerTestStorage->hasChild(parentName: 'posts.redactor', childName: 'posts.update'));
    }

    public function testRemoveChildren(): void
    {
        $innerTestStorage = new ItemsStorage($this->getDataPath());
        $testStorage = new ConcurrentItemsStorageDecorator($innerTestStorage);
        $actionStorage = $this->getItemsStorage();

        $actionStorage->removeChildren('posts.viewer');
        $actionStorage->removeChildren('posts.redactor');

        $testStorage->removeChildren('posts.viewer');
        $this->assertFalse($innerTestStorage->hasChildren('posts.viewer'));
        $this->assertFalse($innerTestStorage->hasChildren('posts.redactor'));
    }

    public function testMultipleCalls(): void
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

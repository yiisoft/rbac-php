<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Role;
use Yiisoft\Rbac\Tests\Common\ItemsStorageTestTrait;

final class ItemsStorageWithConcurrencyNotHandledTest extends TestCase
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

    public function testClear(): void
    {
        $this->markTestSkipped();
    }

    public function testGetAll(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getAll());
    }

    public function testGetByNames(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getByNames(['posts.view']));
    }

    public function testGet(): void
    {
        $this->assertNotNull($this->getEmptyConcurrentItemsStorage()->get('posts.view'));
    }

    public function testExists(): void
    {
        $this->assertTrue($this->getEmptyConcurrentItemsStorage()->exists('posts.view'));
    }

    public function testRoleExists(): void
    {
        $this->assertTrue($this->getEmptyConcurrentItemsStorage()->roleExists('posts.viewer'));
    }

    public function testAddWithCurrentTimestamps(): void
    {
        $testStorage = new ItemsStorage($this->getDataPath());
        $actionStorage = $this->getItemsStorage();

        $actionStorage->add(new Permission('test1'));
        $count = count($actionStorage->getAll());
        $actionStorage->add(new Permission('test2'));

        $testStorage->add(new Permission('test1'));
        $this->assertCount($count, $testStorage->getAll());
    }

    public function testAddWithPastTimestamps(): void
    {
        $this->markTestSkipped();
    }

    public function testUpdate(): void
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

    public function testRemove(): void
    {
        $testStorage = new ItemsStorage($this->getDataPath());
        $actionStorage = $this->getItemsStorage();

        $actionStorage->remove('posts.view');
        $count = count($actionStorage->getAll());
        $actionStorage->remove('posts.create');

        $testStorage->remove('posts.view');
        $this->assertCount($count, $testStorage->getAll());
    }

    public function testGetRoles(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getRoles());
    }

    public function testGetRolesByNames(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getRolesByNames(['posts.viewer']));
    }

    public function testGetRole(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getRole('posts.viewer'));
    }

    public function testClearRoles(): void
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

    public function testGetPermissions(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getPermissions());
    }

    public function testGetPermissionsByNames(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getPermissionsByNames(['posts.view']));
    }

    public function testGetPermission(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getPermission('posts.view'));
    }

    public function testClearPermissions(): void
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

    public function testGetParents(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getParents('posts.view'));
    }

    public function testGetAccessTree(): void
    {
        $this->markTestSkipped();

        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getAccessTree('posts.view'));
    }

    public function testGetDirectChildren(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getDirectChildren('posts.viewer'));
    }

    public function testGetAllChildren(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getAllChildren('posts.viewer'));
    }

    public function testGetAllChildRoles(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getAllChildRoles('posts.redactor'));
    }

    public function testGetAllChildPermissions(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getAllChildPermissions('posts.viewer'));
    }

    public function testHasChildren(): void
    {
        $this->assertTrue($this->getEmptyConcurrentItemsStorage()->hasChildren('posts.viewer'));
    }

    public function testHasChild(): void
    {
        $this->assertTrue($this->getEmptyConcurrentItemsStorage()->hasChild('posts.viewer', 'posts.view'));
    }

    public function testHasDirectChild(): void
    {
        $this->assertTrue($this->getEmptyConcurrentItemsStorage()->hasDirectChild('posts.viewer', 'posts.view'));
    }

    public function testAddChild(): void
    {
        $testStorage = new ItemsStorage($this->getDataPath());
        $actionStorage = $this->getItemsStorage();

        $actionStorage->addChild('posts.viewer', 'posts.create');
        $actionStorage->addChild('posts.viewer', 'posts.update');

        $testStorage->addChild('posts.viewer', 'posts.create');
        $this->assertTrue($testStorage->hasChild('posts.viewer', 'posts.create'));
        $this->assertFalse($testStorage->hasChild('posts.viewer', 'posts.update'));
    }

    public function testRemoveChild(): void
    {
        $testStorage = new ItemsStorage($this->getDataPath());
        $actionStorage = $this->getItemsStorage();

        $actionStorage->removeChild(parentName: 'posts.redactor', childName: 'posts.create');
        $actionStorage->removeChild(parentName: 'posts.redactor', childName: 'posts.update');

        $testStorage->removeChild(parentName: 'posts.redactor', childName: 'posts.create');
        $this->assertFalse($testStorage->hasChild(parentName: 'posts.redactor', childName: 'posts.create'));
        $this->assertTrue($testStorage->hasChild(parentName: 'posts.redactor', childName: 'posts.update'));
    }

    public function testRemoveChildren(): void
    {
        $testStorage = new ItemsStorage($this->getDataPath());
        $actionStorage = $this->getItemsStorage();

        $actionStorage->removeChildren('posts.viewer');
        $actionStorage->removeChildren('posts.redactor');

        $testStorage->removeChildren('posts.viewer');
        $this->assertFalse($testStorage->hasChildren('posts.viewer'));
        $this->assertTrue($testStorage->hasChildren('posts.redactor'));
    }

    public function testMultipleCalls(): void
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
        return $this->createItemsStorage();
    }

    private function getEmptyConcurrentItemsStorage(): ItemsStorageInterface
    {
        $storage = $this->getItemsStorageForModificationAssertions();
        $this->getItemsStorage()->clear();

        return $storage;
    }
}

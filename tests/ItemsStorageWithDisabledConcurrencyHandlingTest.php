<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Tests\Common\ItemsStorageTestTrait;

final class ItemsStorageWithDisabledConcurrencyHandlingTest extends TestCase
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

    public function testGetParentsWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentItemsStorage()->getParents('posts.view'));
    }

    public function testGetAccessTreeWithConcurrency(): void
    {
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
}

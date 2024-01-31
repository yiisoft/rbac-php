<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Tests\Common\ItemsStorageTestTrait;

final class ItemsStorageWithDisabledConcurrencyHandlingTest extends TestCase
{
    use StorageFilePathTrait;
    use ItemsStorageTestTrait {
        setUp as protected traitSetUp;
    }

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
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertNotEmpty($testStorage->getAll());
    }

    public function testGetByNamesWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertNotEmpty($testStorage->getByNames(['posts.view']));
    }

    public function testGetWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertNotNull($testStorage->get('posts.view'));
    }

    public function testExistsWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertTrue($testStorage->exists('posts.view'));
    }

    public function testRoleExistsWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertTrue($testStorage->roleExists('posts.viewer'));
    }

    public function testGetRolesWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertNotEmpty($testStorage->getRoles());
    }

    public function testGetRolesByNamesWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertNotEmpty($testStorage->getRolesByNames(['posts.viewer']));
    }

    public function testGetRoleWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertNotEmpty($testStorage->getRole('posts.viewer'));
    }

    public function testGetPermissionsWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertNotEmpty($testStorage->getPermissions());
    }

    public function testGetPermissionsByNamesWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertNotEmpty($testStorage->getPermissionsByNames(['posts.view']));
    }

    public function testGetPermissionWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertNotEmpty($testStorage->getPermission('posts.view'));
    }

    public function testGetParentsWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertNotEmpty($testStorage->getParents('posts.view'));
    }

    public function testGetAccessTreeWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertNotEmpty($testStorage->getAccessTree('posts.view'));
    }

    public function testGetDirectChildrenWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertNotEmpty($testStorage->getDirectChildren('posts.viewer'));
    }

    public function testGetAllChildrenWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertNotEmpty($testStorage->getAllChildren('posts.viewer'));
    }

    public function testGetAllChildRolesWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertNotEmpty($testStorage->getAllChildRoles('posts.redactor'));
    }

    public function testGetAllChildPermissionsWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertNotEmpty($testStorage->getAllChildPermissions('posts.viewer'));
    }

    public function testHasChildrenWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertTrue($testStorage->hasChildren('posts.viewer'));
    }

    public function testHasChildWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertTrue($testStorage->hasChild('posts.viewer', 'posts.view'));
    }

    public function testHasDirectChildWithConcurrency(): void
    {
        $testStorage = $this->getItemsStorageForModificationAssertions();
        $actionStorage = $this->getItemsStorage();
        $actionStorage->clear();

        $this->assertTrue($testStorage->hasDirectChild('posts.viewer', 'posts.view'));
    }

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage($this->getDataPath());
    }

    protected function getItemsStorageForModificationAssertions(): ItemsStorageInterface
    {
        return str_ends_with($this->name(), 'WithConcurrency') ? $this->createItemsStorage() : $this->getItemsStorage();
    }
}

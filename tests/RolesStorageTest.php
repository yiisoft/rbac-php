<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Files\FileHelper;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Role;

/**
 * @group rbac
 */
final class RolesStorageTest extends TestCase
{
    use FixtureTrait;

    public function testGetAll(): void
    {
        $items = $this->createStorage()->getAll();

        $this->assertCount(10, $items);
        $this->assertEquals(
            [
                'Fast Metabolism',
                'createPost',
                'readPost',
                'deletePost',
                'updatePost',
                'updateAnyPost',
                'withoutChildren',
                'reader',
                'author',
                'admin',
            ],
            array_keys($items)
        );
    }

    public function testClear(): void
    {
        $storage = $this->createStorage();
        $storage->clear();

        $storage = $this->createStorage();

        $this->assertCount(0, $storage->getAll());
        $this->assertCount(0, $storage->getChildren());
        $this->assertCount(0, $storage->getRules());
    }

    public function testClearRoles(): void
    {
        $storage = $this->createStorage();
        $storage->clearRoles();
        $this->assertCount(6, $this->createStorage()->getAll());
    }

    public function testClearPermissions(): void
    {
        $storage = $this->createStorage();
        $storage->clearPermissions();

        $storage = $this->createStorage();
        $this->assertCount(0, $storage->getPermissions());
        $this->assertCount(4, $storage->getAll());
    }

    public function testClearRules(): void
    {
        $storage = $this->createStorage();
        $storage->clearRules();

        $storage = $this->createStorage();
        $this->assertCount(0, $storage->getRules());
    }

    public function testGetPermissionItemByName(): void
    {
        $storage = $this->createStorage();

        $this->assertInstanceOf(Permission::class, $storage->getByName('createPost'));
        $this->assertNull($storage->getByName('nonExistPermission'));
    }

    public function testGetRoleItemByName(): void
    {
        $storage = $this->createStorage();

        $this->assertInstanceOf(Role::class, $storage->getByName('reader'));
        $this->assertNull($storage->getByName('nonExistRole'));
    }

    public function testAddPermissionItem(): void
    {
        $storage = $this->createStorage();

        $item = new Permission('testAddedPermission');
        $storage->add($item);

        $this->assertCount(11, $storage->getAll());
        $this->assertNotNull($storage->getPermissionByName('testAddedPermission'));
    }

    public function testAddRoleItem(): void
    {
        $storage = $this->createStorage();

        $item = new Role('testAddedRole');
        $storage->add($item);

        $this->assertCount(11, $storage->getAll());
        $this->assertNotNull($storage->getRoleByName('testAddedRole'));
    }

    public function testGetRoleByName(): void
    {
        $storage = $this->createStorage();

        $this->assertInstanceOf(Role::class, $storage->getRoleByName('reader'));
        $this->assertNull($storage->getRoleByName('nonExistRole'));
    }

    public function testGetRoles(): void
    {
        $storage = $this->createStorage();

        $this->assertEquals(
            [
                'withoutChildren',
                'reader',
                'author',
                'admin',
            ],
            array_keys($storage->getRoles())
        );
    }

    public function testGetPermissionByName(): void
    {
        $storage = $this->createStorage();

        $this->assertInstanceOf(Permission::class, $storage->getPermissionByName('readPost'));
        $this->assertNull($storage->getPermissionByName('nonExistPermission'));
    }

    public function testGetPermissions(): void
    {
        $storage = $this->createStorage();

        $this->assertEquals(
            [
                'Fast Metabolism',
                'createPost',
                'readPost',
                'deletePost',
                'updatePost',
                'updateAnyPost',
            ],
            array_keys($storage->getPermissions())
        );
    }

    public function testGetChildren(): void
    {
        $children = $this->createStorage()->getChildren();

        $this->assertEquals(['readPost'], array_keys($children['reader']));
        $this->assertEquals(
            [
                'createPost',
                'updatePost',
                'reader',
            ],
            array_keys($children['author'])
        );
        $this->assertEquals(
            [
                'author',
                'updateAnyPost',
            ],
            array_keys($children['admin'])
        );
    }

    public function testGetChildrenByName(): void
    {
        $storage = $this->createStorage();
        $this->assertEquals(
            [
                'createPost',
                'updatePost',
                'reader',
            ],
            array_keys($storage->getChildrenByName('author'))
        );
        $this->assertEmpty($storage->getChildrenByName('itemNotExist'));
    }

    public function testGetRules(): void
    {
        $storage = $this->createStorage();
        $this->assertEquals(['isAuthor'], array_keys($storage->getRules()));
    }

    public function testGetRuleByName(): void
    {
        $storage = $this->createStorage();

        $this->assertInstanceOf(AuthorRule::class, $storage->getRuleByName('isAuthor'));
        $this->assertNull($storage->getRuleByName('nonExistRule'));
    }

    public function testAddChild(): void
    {
        $storage = $this->createStorage();
        $role = $storage->getRoleByName('reader');
        $permission = $storage->getPermissionByName('createPost');

        $storage->addChild($role, $permission);
        $this->assertEquals(
            [
                'readPost',
                'createPost',
            ],
            array_keys($storage->getChildrenByName('reader'))
        );
    }

    public function testHasChildren(): void
    {
        $storage = $this->createStorage();
        $this->assertTrue($storage->hasChildren('reader'));
        $this->assertFalse($storage->hasChildren('withoutChildren'));
        $this->assertFalse($storage->hasChildren('nonExistChildren'));
    }

    public function testRemoveChild(): void
    {
        $storage = $this->createStorage();
        $role = $storage->getRoleByName('reader');
        $permission = $storage->getPermissionByName('readPost');

        $storage->removeChild($role, $permission);
        $this->assertEmpty($storage->getChildrenByName('reader'));
    }

    public function testRemoveChildren(): void
    {
        $storage = $this->createStorage();
        $role = $storage->getRoleByName('reader');

        $storage->removeChildren($role);
        $this->assertEmpty($storage->getChildrenByName('reader'));
    }

    public function testRemoveItem(): void
    {
        $storage = $this->createStorage();
        $storage->remove($storage->getByName('reader'));
        $this->assertEquals(
            [
                'Fast Metabolism',
                'createPost',
                'readPost',
                'deletePost',
                'updatePost',
                'updateAnyPost',
                'withoutChildren',
                'author',
                'admin',
            ],
            array_keys($storage->getAll())
        );

        $this->assertEquals(
            [
                'withoutChildren',
                'author',
                'admin',
            ],
            array_keys($storage->getRoles())
        );
    }

    public function testUpdateItem(): void
    {
        $storage = $this->createStorage();
        $storage->update('reader', $storage->getByName('reader')->withName('new reader'));
        $this->assertEquals(
            [
                'withoutChildren',
                'author',
                'admin',
                'new reader',
            ],
            array_keys($storage->getRoles())
        );
        $this->assertNull($storage->getRoleByName('reader'));
    }

    public function testRemoveRule(): void
    {
        $storage = $this->createStorage();
        $storage->removeRule('isAuthor');
        $this->assertEmpty($storage->getRules());
    }

    public function testAddRule(): void
    {
        $storage = $this->createStorage();
        $storage->addRule(new EasyRule());
        $this->assertEquals(
            [
                'isAuthor',
                EasyRule::class,
            ],
            array_keys($storage->getRules())
        );
    }

    public function testFailCreateDirectory(): void
    {
        $directory = $this->getTempDirectory() . '/file.txt';
        touch($directory);

        $storage = new ItemsStorage($directory);

        $rule = new EasyRule();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create directory "' . $directory . '". mkdir(): File exists');
        $storage->addRule($rule);
    }

    public function testCreateNestedDirectory(): void
    {
        $directory = $this->getTempDirectory() . '/test/create/nested/directory';

        $storage = new ItemsStorage($directory);
        $storage->addRule(new EasyRule());

        $this->assertFileExists($directory . '/rules.php');
    }

    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->getTempDirectory());

        $this->clearFixturesFiles();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        FileHelper::ensureDirectory($this->getTempDirectory());
        FileHelper::clearDirectory($this->getTempDirectory());

        $this->addFixturesFiles();
        parent::setUp();
    }

    private function getTempDirectory(): string
    {
        return __DIR__ . '/temp';
    }

    private function createStorage(): ItemsStorage
    {
        return new ItemsStorage($this->dataPath);
    }
}

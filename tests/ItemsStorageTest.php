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
final class ItemsStorageTest extends TestCase
{
    use FixtureTrait;

    public function testGetAll(): void
    {
        $items = $this
            ->createStorage()
            ->getAll();

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

        $this->assertCount(0, $storage->getAll());
        $this->assertCount(0, $storage->getRoles());
        $this->assertCount(0, $storage->getPermissions());
    }

    public function testClearRoles(): void
    {
        $storage = $this->createStorage();
        $storage->clearRoles();
        $this->assertCount(6, $this
            ->createStorage()
            ->getAll());
    }

    public function testClearPermissions(): void
    {
        $storage = $this->createStorage();
        $storage->clearPermissions();

        $storage = $this->createStorage();
        $this->assertCount(0, $storage->getPermissions());
        $this->assertCount(4, $storage->getAll());
    }

    public function testGetPermissionItemByName(): void
    {
        $storage = $this->createStorage();

        $this->assertInstanceOf(Permission::class, $storage->get('createPost'));
        $this->assertNull($storage->get('nonExistPermission'));
    }

    public function testGetRoleItemByName(): void
    {
        $storage = $this->createStorage();

        $this->assertInstanceOf(Role::class, $storage->get('reader'));
        $this->assertNull($storage->get('nonExistRole'));
    }

    public function testExists(): void
    {
        $storage = $this->createStorage();

        $this->assertTrue($storage->exists('reader'));
        $this->assertFalse($storage->exists('chicken'));
    }

    public function testAddPermissionItem(): void
    {
        $storage = $this->createStorage();

        $item = new Permission('testAddedPermission');
        $storage->add($item);

        $this->assertCount(11, $storage->getAll());
        $this->assertNotNull($storage->getPermission('testAddedPermission'));
    }

    public function testAddRoleItem(): void
    {
        $storage = $this->createStorage();

        $item = new Role('testAddedRole');
        $storage->add($item);

        $this->assertCount(11, $storage->getAll());
        $this->assertNotNull($storage->getRole('testAddedRole'));
    }

    public function testGetRoleByName(): void
    {
        $storage = $this->createStorage();

        $this->assertInstanceOf(Role::class, $storage->getRole('reader'));
        $this->assertNull($storage->getRole('nonExistRole'));
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

        $this->assertInstanceOf(Permission::class, $storage->getPermission('readPost'));
        $this->assertNull($storage->getPermission('nonExistPermission'));
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

    public function dataGetParents(): array
    {
        return [
            [[], 'non-exists'],
            [[], 'deletePost'],
            [['admin'], 'updateAnyPost'],
            [['author', 'admin'], 'reader'],
        ];
    }

    /**
     * @dataProvider dataGetParents
     */
    public function testGetParents(array $expected, string $name): void
    {
        $storage = $this->createStorage();

        $this->assertSame($expected, array_keys($storage->getParents($name)));
    }

    public function testGetChildren(): void
    {
        $storage = $this->createStorage();
        $this->assertEquals(
            [
                'createPost',
                'updatePost',
                'reader',
            ],
            array_keys($storage->getChildren('author'))
        );
        $this->assertEmpty($storage->getChildren('itemNotExist'));
    }

    public function testAddChild(): void
    {
        $storage = $this->createStorage();

        $storage->addChild('reader', 'createPost');
        $this->assertEquals(
            [
                'readPost',
                'createPost',
            ],
            array_keys($storage->getChildren('reader'))
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

        $storage->removeChild('reader', 'readPost');
        $this->assertEmpty($storage->getChildren('reader'));
    }

    public function testRemoveChildren(): void
    {
        $storage = $this->createStorage();

        $storage->removeChildren('reader');
        $this->assertEmpty($storage->getChildren('reader'));
    }

    public function testRemoveItem(): void
    {
        $storage = $this->createStorage();
        $storage->remove('reader');
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
        $storage->update('reader', $storage
            ->get('reader')
            ->withName('new reader'));

        $children = $storage->getChildren('author');

        $this->assertSame(
            [
                'withoutChildren',
                'author',
                'admin',
                'new reader',
            ],
            array_keys($storage->getRoles())
        );
        $this->assertNull($storage->getRole('reader'));
        $this->assertSame(
            [
                'createPost',
                'updatePost',
                'new reader',
            ],
            array_keys($children)
        );
        $this->assertSame('new reader', $children['new reader']->getName());
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

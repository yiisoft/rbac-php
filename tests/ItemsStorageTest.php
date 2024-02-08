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

    public $opcacheInvalidated = false;
    private const EMPTY_STORAGE_TESTS = [
        'testSaveWithNullAttributes',
        'testLoadWithCustomGetFileUpdatedAt',
        'testSaveAndInvalidateOpcacheWithExtension',
        'testSaveAndInvalidateOpcacheWithoutExtension',
    ];

    protected function setUp(): void
    {
        if ($this->name() === 'testFailCreateDirectory' || $this->name() === 'testCreateNestedDirectory') {
            FileHelper::ensureDirectory($this->getTempDirectory());
            FileHelper::clearDirectory($this->getTempDirectory());
        }

        if ($this->name() === 'testSaveAndInvalidateOpcacheWithExtension') {
            uopz_set_return(
                'function_exists',
                static function (string $function): bool {
                    return $function === 'opcache_invalidate' ? true : function_exists($function);
                },
                true,
            );

            $storage = $this;
            uopz_set_return(
                'opcache_invalidate',
                static function (string $filename, bool $force = true) use ($storage): bool {
                    $storage->opcacheInvalidated = true;

                    return true;
                },
                true,
            );
        }

        if ($this->name() === 'testSaveAndInvalidateOpcacheWithoutExtension') {
            uopz_set_return(
                'function_exists',
                static function (string $function): bool {
                    return $function === 'opcache_invalidate' ? false : function_exists($function);
                },
                true,
            );
        }

        if (!in_array($this->name(), self::EMPTY_STORAGE_TESTS, strict: true)) {
            $this->traitSetUp();
        }
    }

    protected function tearDown(): void
    {
        if ($this->name() === 'testFailCreateDirectory' || $this->name() === 'testCreateNestedDirectory') {
            FileHelper::removeDirectory($this->getTempDirectory());
        }

        if ($this->name() === 'testSaveAndInvalidateOpcacheWithExtension') {
            uopz_unset_return('function_exists');
            uopz_unset_return('opcache_invalidate');
            $this->opcacheInvalidated = false;
        }

        if ($this->name() === 'testSaveAndInvalidateOpcacheWithoutExtension') {
            uopz_unset_return('function_exists');
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

    public function testSaveWithNullAttributes(): void
    {
        $this->createItemsStorage()->add(new Permission('test'));

        $data = require $this->getDataPath() . DIRECTORY_SEPARATOR . 'items.php';
        $this->assertSame([['name' => 'test', 'type' => 'permission']], $data);
    }

    public function testLoadWithCustomGetFileUpdatedAt(): void
    {
        $time = 1683707079;
        $storage = new ItemsStorage(
            $this->getDataPath(),
            getFileUpdatedAt: static fn (string $filename): int|false => $time,
        );
        $storage->add(new Permission('test'));
        $storage->load();

        $this->assertSame($time, $storage->get('test')->getCreatedAt());
    }

    public function testSaveAndInvalidateOpcacheWithExtension(): void
    {
        $storage = $this->createItemsStorage();
        $storage->add(new Permission('test'));

        $this->assertCount(1, $storage->getAll());
        $this->assertTrue($this->opcacheInvalidated);
    }

    public function testSaveAndInvalidateOpcacheWithoutExtension(): void
    {
        $storage = $this->createItemsStorage();
        $storage->add(new Permission('test'));

        $this->assertCount(1, $storage->getAll());
        $this->assertFalse($this->opcacheInvalidated);
    }

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage($this->getDataPath());
    }

    protected function getItemsStorageForModificationAssertions(): ItemsStorageInterface
    {
        return $this->getItemsStorage();
    }

    private function getTempDirectory(): string
    {
        return __DIR__ . '/temp';
    }
}

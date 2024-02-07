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

<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests\ItemsStorage;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Files\FileHelper;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Php\Tests\StorageFilePathTrait;
use Yiisoft\Rbac\Tests\Common\ItemsStorageTestTrait;

use function in_array;

final class ItemsStorageTest extends TestCase
{
    use ItemsStorageTestTrait {
        setUp as protected traitSetUp;
    }
    use StorageFilePathTrait;

    private const EMPTY_STORAGE_TESTS = [
        'testSaveWithNullAttributes',
        'testSaveWithAllAttributes',
        'testLoadWithCustomGetFileUpdatedAt',
    ];

    protected function setUp(): void
    {
        if ($this->name() === 'testCreateDirectoryException') {
            FileHelper::ensureDirectory($this->getTempDirectory());
            FileHelper::clearDirectory($this->getTempDirectory());
        }

        if (!in_array($this->name(), self::EMPTY_STORAGE_TESTS, strict: true)) {
            $this->traitSetUp();
        }
    }

    protected function tearDown(): void
    {
        if ($this->name() === 'testCreateDirectoryException') {
            FileHelper::removeDirectory($this->getTempDirectory());
        }

        $this->clearStoragesFiles();
    }

    public function testCreateDirectoryException(): void
    {
        $directory = $this->getTempDirectory() . '/file.txt';
        touch($directory);

        $storage = new ItemsStorage($directory . '/items.php');
        $permission = new Permission('createPost');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create directory "' . $directory . '". mkdir(): File exists');
        $storage->add($permission);
    }

    public function testSaveWithNullAttributes(): void
    {
        $this->createItemsStorage()->add(new Permission('test'));

        $data = require $this->getStoragesDirectory() . DIRECTORY_SEPARATOR . 'items.php';
        $this->assertSame([['name' => 'test', 'type' => 'permission']], $data);
    }

    public function testSaveAndLoadWithAllAttributes(): void
    {
        $time = time();
        $permission = (new Permission('testName'))
            ->withDescription('testDescription')
            ->withRuleName('testRule')
            ->withCreatedAt($time)
            ->withUpdatedAt($time);
        $storage = $this->createItemsStorage();
        $storage->add($permission);
        $storage = $this->createItemsStorage();

        $this->assertEquals($permission, $storage->get('testName'));
    }

    public function testLoadWithCustomGetFileUpdatedAt(): void
    {
        $time = 1683707079;
        $storage = $this->createItemsStorage();
        $storage->add(new Permission('test'));

        $storage = new ItemsStorage(
            $this->getItemsStorageFilePath(),
            getFileUpdatedAt: static fn (string $filePath): int|false => $time,
        );
        $this->assertSame($time, $storage->get('test')->getCreatedAt());
    }

    public function testGetFileUpdatedAtException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('getFileUpdatedAt callable must return a UNIX timestamp.');
        new ItemsStorage(
            $this->getItemsStorageFilePath(),
            getFileUpdatedAt: static fn (string $filePath): string => 'test',
        );
    }

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage($this->getItemsStorageFilePath());
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

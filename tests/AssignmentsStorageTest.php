<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Php\AssignmentsStorage;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Tests\Common\AssignmentsStorageTestTrait;

final class AssignmentsStorageTest extends TestCase
{
    use AssignmentsStorageTestTrait {
        setUp as protected traitSetUp;
    }
    use StorageFilePathTrait;

    protected function setUp(): void
    {
        $this->traitSetUp();
    }

    protected function tearDown(): void
    {
        $this->clearStoragesFiles();
    }

    public function testLoad(): void
    {
        $this->assertNotEmpty($this->createAssignmentsStorage()->getAll());
    }

    public function testGetFileUpdatedAtException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('getFileUpdatedAt callable must return a UNIX timestamp.');
        new AssignmentsStorage(
            $this->getAssignmentsStorageFilePath(),
            getFileUpdatedAt: static fn(string $filePath): string => 'test',
        );
    }

    public function testRemoveNonExistingDoesNotSaveFile(): void
    {
        $filePath = $this->getAssignmentsStorageFilePath();
        $storage = new AssignmentsStorage($filePath);

        touch($filePath, time() - 100);
        clearstatcache();
        $modifiedTimeBefore = filemtime($filePath);

        $storage->remove(itemName: 'Operator', userId: 'john');

        clearstatcache();
        $this->assertSame($modifiedTimeBefore, filemtime($filePath));
    }

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage($this->getItemsStorageFilePath());
    }

    protected function createAssignmentsStorage(): AssignmentsStorageInterface
    {
        return new AssignmentsStorage($this->getAssignmentsStorageFilePath());
    }

    protected function getAssignmentsStorageForModificationAssertions(): AssignmentsStorageInterface
    {
        return $this->getAssignmentsStorage();
    }
}

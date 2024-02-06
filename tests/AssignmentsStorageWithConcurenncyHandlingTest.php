<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Php\AssignmentsStorage;
use Yiisoft\Rbac\Php\ConcurrentAssignmentsStorageDecorator;
use Yiisoft\Rbac\Php\ConcurrentItemsStorageDecorator;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Tests\Common\AssignmentsStorageTestTrait;

final class AssignmentsStorageWithConcurenncyHandlingTest extends TestCase
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
        $this->clearFixturesFiles();
    }

    public function testGetAllWithConcurrency(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentAssignmentsStorage()->getAll());
    }

    public function testGetByUserIdWithConcurrency(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentAssignmentsStorage()->getByUserId('john'));
    }

    public function testGetByItemNamesWithConcurrency(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentAssignmentsStorage()->getByItemNames(['Researcher']));
    }

    public function testGetWithConcurrency(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentAssignmentsStorage()->get(itemName: 'Researcher', userId: 'john'));
    }

    public function testExistsWithConcurrency(): void
    {
        $this->assertFalse(
            $this->getEmptyConcurrentAssignmentsStorage()->exists(itemName: 'Researcher', userId: 'john'),
        );
    }

    public function testUserHasItemWithConcurrency(): void
    {
        $this->assertFalse(
            $this->getEmptyConcurrentAssignmentsStorage()->userHasItem(userId: 'john', itemNames: ['Researcher']),
        );
    }

    public function testFilterUserItemNamesWithConcurrency(): void
    {
        $this->assertEmpty(
            $this->getEmptyConcurrentAssignmentsStorage()->filterUserItemNames(
                userId: 'john',
                itemNames: ['Researcher'],
            ),
        );
    }

    public function testHasItemWithConcurrency(): void
    {
        $this->assertFalse($this->getEmptyConcurrentAssignmentsStorage()->hasItem('Researcher'));
    }

    public function removeByItemName(string $itemName): void
    {
    }

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ConcurrentItemsStorageDecorator(new ItemsStorage($this->getDataPath()));
    }

    protected function createAssignmentsStorage(): AssignmentsStorageInterface
    {
        return new ConcurrentAssignmentsStorageDecorator(new AssignmentsStorage($this->getDataPath()));
    }

    protected function getAssignmentsStorageForModificationAssertions(): AssignmentsStorageInterface
    {
        return $this->createAssignmentsStorage();
    }

    private function getEmptyConcurrentAssignmentsStorage(): AssignmentsStorageInterface
    {
        $storage = $this->getAssignmentsStorageForModificationAssertions();
        $this->getAssignmentsStorage()->clear();

        return $storage;
    }
}

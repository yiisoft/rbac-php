<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Rbac\Assignment;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Php\AssignmentsStorage;
use Yiisoft\Rbac\Php\ConcurrentAssignmentsStorageDecorator;
use Yiisoft\Rbac\Php\ConcurrentItemsStorageDecorator;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Tests\Common\AssignmentsStorageTestTrait;

final class AssignmentsStorageWithConcurrencyHandledTest extends TestCase
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

    public function testGetAll(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentAssignmentsStorage()->getAll());
    }

    public function testGetByUserId(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentAssignmentsStorage()->getByUserId('john'));
    }

    public function testGetByItemNames(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentAssignmentsStorage()->getByItemNames(['Researcher']));
    }

    public function testGet(): void
    {
        $this->assertEmpty($this->getEmptyConcurrentAssignmentsStorage()->get(itemName: 'Researcher', userId: 'john'));
    }

    public function testExists(): void
    {
        $this->assertFalse(
            $this->getEmptyConcurrentAssignmentsStorage()->exists(itemName: 'Researcher', userId: 'john'),
        );
    }

    public function testUserHasItem(): void
    {
        $this->assertFalse(
            $this->getEmptyConcurrentAssignmentsStorage()->userHasItem(userId: 'john', itemNames: ['Researcher']),
        );
    }

    public function testFilterUserItemNames(): void
    {
        $this->assertEmpty(
            $this->getEmptyConcurrentAssignmentsStorage()->filterUserItemNames(
                userId: 'john',
                itemNames: ['Researcher'],
            ),
        );
    }

    public function testAdd(): void
    {
        $innerTestStorage = new AssignmentsStorage($this->getAssignmentsStorageFilePath());
        $testStorage = new ConcurrentAssignmentsStorageDecorator($innerTestStorage);
        $actionStorage = $this->getAssignmentsStorage();

        $time = time();
        $count = count($actionStorage->getByItemNames(['Researcher']));
        $actionStorage->add(new Assignment(userId: 'jack', itemName: 'Researcher', createdAt: $time));
        $actionStorage->add(new Assignment(userId: 'jeff', itemName: 'Researcher', createdAt: $time));

        $testStorage->add(new Assignment(userId: 'jack', itemName: 'Researcher', createdAt: $time));
        $this->assertCount($count + 2, $innerTestStorage->getByItemNames(['Researcher']));
    }

    public function testHasItem(): void
    {
        $this->assertFalse($this->getEmptyConcurrentAssignmentsStorage()->hasItem('Researcher'));
    }

    public function testRenameItem(): void
    {
        $innerTestStorage = new AssignmentsStorage($this->getAssignmentsStorageFilePath());
        $testStorage = new ConcurrentAssignmentsStorageDecorator($innerTestStorage);
        $actionStorage = $this->getAssignmentsStorage();

        $actionStorage->renameItem('Researcher', 'Researcher1');
        $actionStorage->renameItem('Accountant', 'Accountant1');

        $testStorage->renameItem('Researcher', 'Researcher1');
        $this->assertTrue($innerTestStorage->hasItem('Accountant1'));
    }

    public function testRemove(): void
    {
        $innerTestStorage = new AssignmentsStorage($this->getAssignmentsStorageFilePath());
        $testStorage = new ConcurrentAssignmentsStorageDecorator($innerTestStorage);
        $actionStorage = $this->getAssignmentsStorage();

        $count = count($actionStorage->getByUserId('john'));
        $actionStorage->remove(itemName: 'Researcher', userId: 'john');
        $actionStorage->remove(itemName: 'Accountant', userId: 'john');

        $testStorage->remove(itemName: 'Researcher', userId: 'john');
        $this->assertCount($count - 2, $innerTestStorage->getByUserId('john'));
    }

    public function testRemoveByUserId(): void
    {
        $innerTestStorage = new AssignmentsStorage($this->getAssignmentsStorageFilePath());
        $testStorage = new ConcurrentAssignmentsStorageDecorator($innerTestStorage);
        $actionStorage = $this->getAssignmentsStorage();

        $actionStorage->removeByUserId('john');
        $actionStorage->removeByUserId('jack');

        $testStorage->removeByUserId('john');
        $this->assertEmpty($innerTestStorage->getByUserId('jack'));
    }

    public function testRemoveByItemName(): void
    {
        $innerTestStorage = new AssignmentsStorage($this->getAssignmentsStorageFilePath());
        $testStorage = new ConcurrentAssignmentsStorageDecorator($innerTestStorage);
        $actionStorage = $this->getAssignmentsStorage();

        $actionStorage->removeByItemName('Researcher');
        $actionStorage->removeByItemName('Accountant');

        $testStorage->removeByItemName('Researcher');
        $this->assertEmpty($innerTestStorage->getByItemNames(['Accountant']));
    }

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ConcurrentItemsStorageDecorator(new ItemsStorage($this->getItemsStorageFilePath()));
    }

    protected function createAssignmentsStorage(): AssignmentsStorageInterface
    {
        return new ConcurrentAssignmentsStorageDecorator(new AssignmentsStorage($this->getAssignmentsStorageFilePath()));
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

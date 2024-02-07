<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Rbac\Assignment;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Php\AssignmentsStorage;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Tests\Common\AssignmentsStorageTestTrait;

final class AssignmentsStorageWithConcurrencyNotHandled extends TestCase
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

    public function testGetAll(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentAssignmentsStorage()->getAll());
    }

    public function testGetByUserId(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentAssignmentsStorage()->getByUserId('john'));
    }

    public function testGetByItemNames(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentAssignmentsStorage()->getByItemNames(['Researcher']));
    }

    public function testGet(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentAssignmentsStorage()->get(itemName: 'Researcher', userId: 'john'));
    }

    public function testExists(): void
    {
        $this->assertTrue(
            $this->getEmptyConcurrentAssignmentsStorage()->exists(itemName: 'Researcher', userId: 'john'),
        );
    }

    public function testUserHasItem(): void
    {
        $this->assertTrue(
            $this->getEmptyConcurrentAssignmentsStorage()->userHasItem(userId: 'john', itemNames: ['Researcher']),
        );
    }

    public function testFilterUserItemNames(): void
    {
        $this->assertNotEmpty(
            $this->getEmptyConcurrentAssignmentsStorage()->filterUserItemNames(
                userId: 'john',
                itemNames: ['Researcher'],
            ),
        );
    }

    public function testAdd(): void
    {
        $testStorage = new AssignmentsStorage($this->getDataPath());
        $actionStorage = $this->getAssignmentsStorage();

        $time = time();
        $count = count($actionStorage->getByItemNames(['Researcher']));
        $actionStorage->add(new Assignment(userId: 'jack', itemName: 'Researcher', createdAt: $time));
        $actionStorage->add(new Assignment(userId: 'jeff', itemName: 'Researcher', createdAt: $time));

        $testStorage->add(new Assignment(userId: 'jack', itemName: 'Researcher', createdAt: $time));
        $this->assertCount($count + 1, $testStorage->getByItemNames(['Researcher']));
    }

    public function testHasItem(): void
    {
        $this->assertTrue($this->getEmptyConcurrentAssignmentsStorage()->hasItem('Researcher'));
    }

    public function testRenameItem(): void
    {
        $testStorage = new AssignmentsStorage($this->getDataPath());
        $actionStorage = $this->getAssignmentsStorage();

        $actionStorage->renameItem('Researcher', 'Researcher1');
        $actionStorage->renameItem('Accountant', 'Accountant1');

        $testStorage->renameItem('Researcher', 'Researcher1');
        $this->assertFalse($testStorage->hasItem('Accountant1'));
    }

    public function testRemove(): void
    {
        $testStorage = new AssignmentsStorage($this->getDataPath());
        $actionStorage = $this->getAssignmentsStorage();

        $count = count($actionStorage->getByUserId('john'));
        $actionStorage->remove(itemName: 'Researcher', userId: 'john');
        $actionStorage->remove(itemName: 'Accountant', userId: 'john');

        $testStorage->remove(itemName: 'Researcher', userId: 'john');
        $this->assertCount($count - 1, $testStorage->getByUserId('john'));
    }

    public function testRemoveByUserId(): void
    {
        $testStorage = new AssignmentsStorage($this->getDataPath());
        $actionStorage = $this->getAssignmentsStorage();

        $actionStorage->removeByUserId('john');
        $actionStorage->removeByUserId('jack');

        $testStorage->removeByUserId('john');
        $this->assertNotEmpty($testStorage->getByUserId('jack'));
    }

    public function testRemoveByItemName(): void
    {
        $testStorage = new AssignmentsStorage($this->getDataPath());
        $actionStorage = $this->getAssignmentsStorage();

        $actionStorage->removeByItemName('Researcher');
        $actionStorage->removeByItemName('Accountant');

        $testStorage->removeByItemName('Researcher');
        $this->assertNotEmpty($testStorage->getByItemNames(['Accountant']));
    }

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage($this->getDataPath());
    }

    protected function createAssignmentsStorage(): AssignmentsStorageInterface
    {
        return new AssignmentsStorage($this->getDataPath());
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

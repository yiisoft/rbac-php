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
        $this->clearFixturesFiles();
    }

    public function testGetAllWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentAssignmentsStorage()->getAll());
    }

    public function testGetByUserIdWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentAssignmentsStorage()->getByUserId('john'));
    }

    public function testGetByItemNamesWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentAssignmentsStorage()->getByItemNames(['Researcher']));
    }

    public function testGetWithConcurrency(): void
    {
        $this->assertNotEmpty($this->getEmptyConcurrentAssignmentsStorage()->get(itemName: 'Researcher', userId: 'john'));
    }

    public function testExistsWithConcurrency(): void
    {
        $this->assertTrue(
            $this->getEmptyConcurrentAssignmentsStorage()->exists(itemName: 'Researcher', userId: 'john'),
        );
    }

    public function testUserHasItemWithConcurrency(): void
    {
        $this->assertTrue(
            $this->getEmptyConcurrentAssignmentsStorage()->userHasItem(userId: 'john', itemNames: ['Researcher']),
        );
    }

    public function testFilterUserItemNamesWithConcurrency(): void
    {
        $this->assertNotEmpty(
            $this->getEmptyConcurrentAssignmentsStorage()->filterUserItemNames(
                userId: 'john',
                itemNames: ['Researcher'],
            ),
        );
    }

    public function testAddWithConcurrency(): void
    {
        $testStorage = new AssignmentsStorage($this->getDataPath());
        $actionStorage = $this->getAssignmentsStorage();

        $time = time();
        $actionStorage->add(new Assignment(userId: 'jack', itemName: 'Researcher', createdAt: $time));
        $count = count($actionStorage->getByItemNames(['Researcher']));
        $actionStorage->add(new Assignment(userId: 'jeff', itemName: 'Researcher', createdAt: $time));

        $testStorage->add(new Assignment(userId: 'jack', itemName: 'Researcher', createdAt: $time));
        $this->assertCount($count, $testStorage->getByItemNames(['Researcher']));
    }

    public function testHasItemWithConcurrency(): void
    {
        $this->assertTrue($this->getEmptyConcurrentAssignmentsStorage()->hasItem('Researcher'));
    }

    public function testRenameItemWithConcurrency(): void
    {
        $testStorage = new AssignmentsStorage($this->getDataPath());
        $actionStorage = $this->getAssignmentsStorage();

        $actionStorage->renameItem('Researcher', 'Researcher1');
        $actionStorage->renameItem('Accountant', 'Accountant1');

        $testStorage->renameItem('Researcher', 'Researcher1');
        $this->assertFalse($testStorage->hasItem('Accountant1'));
    }

    public function testRemoveWithConcurrency(): void
    {
        $testStorage = new AssignmentsStorage($this->getDataPath());
        $actionStorage = $this->getAssignmentsStorage();

        $actionStorage->remove(itemName: 'Researcher', userId: 'john');
        $count = count($actionStorage->getByUserId('john'));
        $actionStorage->remove(itemName: 'Accountant', userId: 'john');

        $testStorage->remove(itemName: 'Researcher', userId: 'john');
        $this->assertCount($count, $testStorage->getByUserId('john'));
    }

    public function testRemoveByUserIdWithConcurrency(): void
    {
        $testStorage = new AssignmentsStorage($this->getDataPath());
        $actionStorage = $this->getAssignmentsStorage();

        $actionStorage->removeByUserId('john');
        $actionStorage->removeByUserId('jack');

        $testStorage->removeByUserId('john');
        $this->assertNotEmpty($testStorage->getByUserId('jack'));
    }

    public function testRemoveByItemNameWithConcurrency(): void
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
        return str_ends_with($this->name(), 'WithConcurrency')
            ? $this->createAssignmentsStorage()
            : $this->getAssignmentsStorage();
    }

    private function getEmptyConcurrentAssignmentsStorage(): AssignmentsStorageInterface
    {
        $storage = $this->getAssignmentsStorageForModificationAssertions();
        $this->getAssignmentsStorage()->clear();

        return $storage;
    }
}

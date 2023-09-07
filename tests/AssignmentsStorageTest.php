<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Php\AssignmentsStorage;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Role;
use Yiisoft\Rbac\Tests\Common\AssignmentsStorageTestTrait;

final class AssignmentsStorageTest extends TestCase
{
    use AssignmentsStorageTestTrait;
    use FixtureTrait;

    protected function setUp(): void
    {
        $this->populateStorage();
    }

    protected function tearDown(): void
    {
        $this->clearFixturesFiles();
    }

    private function populateStorage(): void
    {
        $itemsStorage = $this->getItemsStorage();
        $assignmentsStorage = $this->getStorage();
        $fixtures = $this->getFixtures();
        foreach ($fixtures['items'] as $itemData) {
            $name = $itemData['name'];
            $item = $itemData['type'] === Item::TYPE_PERMISSION ? new Permission($name) : new Role($name);
            $item = $item
                ->withCreatedAt($itemData['createdAt'])
                ->withUpdatedAt($itemData['updatedAt']);
            $itemsStorage->add($item);
        }

        foreach ($fixtures['assignments'] as $assignmentData) {
            $assignmentsStorage->add($assignmentData['itemName'], $assignmentData['userId']);
        }
    }

    private function getItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage($this->getDataPath());
    }

    private function getStorage(): AssignmentsStorageInterface
    {
        return new AssignmentsStorage($this->getDataPath());
    }
}

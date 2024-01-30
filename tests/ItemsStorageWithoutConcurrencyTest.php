<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Php\ItemsStorage;

final class ItemsStorageWithoutConcurrencyTest extends TestCase
{
    use FixtureTrait;

    public function testConcurrencyDisabled(): void
    {
        $storage1 = $this->createItemsStorage();
        $storage2 = $this->createItemsStorage();

        $storage1->add(new Permission('test'));
        $item = $storage2->get('test');

        $this->assertNull($item);
    }

    private function createItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage($this->getDataPath());
    }
}

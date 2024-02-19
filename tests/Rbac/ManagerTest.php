<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests\Rbac;

use PHPUnit\Framework\TestCase;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Php\AssignmentsStorage;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Php\Tests\StorageFilePathTrait;
use Yiisoft\Rbac\Tests\Common\ManagerConfigurationTestTrait;
use Yiisoft\Rbac\Tests\Common\ManagerLogicTestTrait;

class ManagerTest extends TestCase
{
    use ManagerConfigurationTestTrait;
    use ManagerLogicTestTrait;
    use StorageFilePathTrait;

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage($this->getItemsStorageFilePath());
    }

    protected function createAssignmentsStorage(): AssignmentsStorageInterface
    {
        return new AssignmentsStorage($this->getAssignmentsStorageFilePath());
    }
}

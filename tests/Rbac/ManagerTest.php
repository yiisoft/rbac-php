<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests\Rbac;

use PHPUnit\Framework\TestCase;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Php\AssignmentsStorage;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Tests\Common\ManagerConfigurationTestTrait;
use Yiisoft\Rbac\Tests\Common\ManagerLogicTestTrait;

class ManagerTest extends TestCase
{
    use ManagerConfigurationTestTrait;
    use ManagerLogicTestTrait;

    protected function createItemsStorage(): ItemsStorageInterface
    {
        return new ItemsStorage($this->getDataPath());
    }

    protected function createAssignmentsStorage(): AssignmentsStorageInterface
    {
        return new AssignmentsStorage($this->getDataPath());
    }

    private function getDataPath(): string
    {
        return sys_get_temp_dir() . '/' . str_replace('\\', '_', static::class) . uniqid('', more_entropy: false);
    }
}

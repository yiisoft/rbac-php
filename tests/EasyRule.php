<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\RuleInterface;

final class EasyRule implements RuleInterface
{
    public function execute(string $userId, Item $item, array $parameters = []): bool
    {
        return true;
    }

    public function getName(): string
    {
        return self::class;
    }
}

<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\Rule;

final class EasyRule extends Rule
{
    public function __construct()
    {
        parent::__construct(self::class);
    }

    public function execute(string $userId, Item $item, array $parameters = []): bool
    {
        return true;
    }
}

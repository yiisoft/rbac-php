<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests\Support;

final class TestHelper
{
    private function __construct() {}

    public static function getCurrentErrorHandler(): ?callable
    {
        $currentHandler = set_error_handler(static fn() => true);
        restore_error_handler();
        return $currentHandler;
    }
}

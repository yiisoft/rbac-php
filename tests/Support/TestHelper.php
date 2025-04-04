<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests\Support;

use function sprintf;

final class TestHelper
{
    private function __construct()
    {
    }

    public static function getCurrentErrorHandler(): callable|null
    {
        $currentHandler = set_error_handler(static fn() => true);
        restore_error_handler();
        return $currentHandler;
    }

    public static function getDirectoryPermissions(string $path): int
    {
        return octdec(substr(sprintf('%o', fileperms($path)), -4));
    }
}

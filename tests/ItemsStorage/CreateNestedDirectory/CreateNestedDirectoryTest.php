<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests\ItemsStorage\CreateNestedDirectory;

use PHPUnit\Framework\TestCase;
use Yiisoft\Files\FileHelper;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Php\Tests\Support\TestHelper;

use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertSame;

final class CreateNestedDirectoryTest extends TestCase
{
    private const RUNTIME_DIRECTORY = __DIR__ . '/runtime';

    protected function setUp(): void
    {
        FileHelper::ensureDirectory(self::RUNTIME_DIRECTORY);
        FileHelper::clearDirectory(self::RUNTIME_DIRECTORY);
    }

    protected function tearDown(): void
    {
        FileHelper::removeDirectory(self::RUNTIME_DIRECTORY);
    }

    public function testBase(): void
    {
        $directory = self::RUNTIME_DIRECTORY . '/test/create/nested/directory';

        $storage = new ItemsStorage($directory . '/items.php');
        $storage->add(new Permission('createPost'));

        assertFileExists($directory . '/items.php');
    }

    public function testRestoreErrorHandler(): void
    {
        $directory = self::RUNTIME_DIRECTORY . '/test/create/nested/directory';
        $errorHandler = static fn() => true;
        set_error_handler($errorHandler);

        try {
            $storage = new ItemsStorage($directory . '/items.php');
            $storage->add(new Permission('createPost'));
            $currentErrorHandler = TestHelper::getCurrentErrorHandler();
        } finally {
            restore_error_handler();
        }

        assertSame($errorHandler, $currentErrorHandler);
    }

    /**
     * @requires OS Linux
     */
    public function testDirectoryPermission(): void
    {
        $directory = self::RUNTIME_DIRECTORY . '/test/create/nested/directory-permissions';

        $storage = new ItemsStorage($directory . '/items.php');
        $storage->add(new Permission('createPost'));

        $this->assertSame(0755, TestHelper::getDirectoryPermissions($directory));
    }
}

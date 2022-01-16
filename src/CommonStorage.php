<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php;

use RuntimeException;
use Yiisoft\VarDumper\VarDumper;

use function dirname;
use function function_exists;

abstract class CommonStorage
{
    /**
     * Loads the authorization data from a PHP script file.
     *
     * @param string $file The file path.
     *
     * @return mixed The authorization data.
     *
     * @see saveToFile()
     */
    protected function loadFromFile(string $file): array
    {
        if (is_file($file)) {
            /**
             * @psalm-suppress UnresolvableInclude
             */
            return require $file;
        }

        return [];
    }

    /**
     * Saves the authorization data to a PHP script file.
     *
     * @param array $data The authorization data
     * @param string $file The file path.
     *
     * @see loadFromFile()
     */
    protected function saveToFile(array $data, string $file): void
    {
        $directory = dirname($file);

        if (!is_dir($directory)) {
            set_error_handler(static function (int $errorNumber, string $errorString) use ($directory): bool {
                // Handle race condition.
                // See https://github.com/kalessil/phpinspectionsea/blob/master/docs/probable-bugs.md#mkdir-race-condition
                if (!is_dir($directory)) {
                    throw new RuntimeException(
                        sprintf('Failed to create directory "%s". ', $directory) . $errorString,
                        $errorNumber
                    );
                }
                return true;
            });
            mkdir($directory, 0775, true);
            restore_error_handler();
        }

        file_put_contents($file, "<?php\n\nreturn " . VarDumper::create($data)->export() . ";\n", LOCK_EX);
        $this->invalidateScriptCache($file);
    }

    /**
     * Invalidates precompiled script cache (such as OPCache) for the given file.
     *
     * @param string $file The file path.
     */
    protected function invalidateScriptCache(string $file): void
    {
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
        }
    }
}

<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php;

use RuntimeException;
use Yiisoft\VarDumper\VarDumper;

use function dirname;
use function function_exists;

trait FileStorageTrait
{
    private string $filePath;
    /**
     * @var callable
     */
    private $getFileUpdatedAt;

    public function getFileUpdatedAt(): int
    {
        $getFileUpdatedAt = $this->getFileUpdatedAt;
        $fileUpdatedAt = $getFileUpdatedAt($this->filePath);
        if (!is_int($fileUpdatedAt)) {
            throw new RuntimeException('getFileUpdatedAt callable must return a UNIX timestamp.');
        }

        return $fileUpdatedAt;
    }

    /**
     * Loads the authorization data from a PHP script file.
     *
     * @param string $file The file path.
     *
     * @return array The authorization data.
     * @psalm-suppress MixedInferredReturnType
     * @link https://github.com/yiisoft/rbac-php/issues/72
     *
     * @see saveToFile()
     */
    private function loadFromFile(string $filePath): array
    {
        if (is_file($filePath)) {
            /**
             * @psalm-suppress MixedReturnStatement
             * @link https://github.com/yiisoft/rbac-php/issues/72
             */
            return require $filePath;
        }

        return [];
    }

    /**
     * Saves the authorization data to a PHP script file.
     *
     * @param array $data The authorization data.
     * @param string $filePath The file path.
     *
     * @see loadFromFile()
     */
    private function saveToFile(array $data): void
    {
        $directory = dirname($this->filePath);

        if (!is_dir($directory)) {
            set_error_handler(static function (int $errorNumber, string $errorString) use ($directory): void {
                if (!is_dir($directory)) {
                    throw new RuntimeException(
                        sprintf('Failed to create directory "%s". ', $directory) . $errorString,
                        $errorNumber,
                    );
                }
            });
            mkdir($directory, permissions: 0775, recursive: true);
            restore_error_handler();
        }

        file_put_contents($this->filePath, "<?php\n\nreturn " . VarDumper::create($data)->export() . ";\n", LOCK_EX);
        $this->invalidateScriptCache();
    }

    /**
     * Invalidates precompiled script cache (such as OPCache) for the given file.
     *
     * @infection-ignore-all
     */
    private function invalidateScriptCache(): void
    {
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($this->filePath, force: true);
        }
    }

    private function initFileProperties(string $filePath, ?callable $getFileUpdatedAt): void
    {
        $this->filePath = $filePath;
        $this->getFileUpdatedAt = $getFileUpdatedAt ?? static fn (string $filePath): int|false => @filemtime($filePath);
    }
}

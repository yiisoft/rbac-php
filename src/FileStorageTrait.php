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
    private ?int $currentFileUpdatedAt = null;
    private bool $enableConcurrencyHandling;
    private bool $previousEnableConcurrencyHandling;

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
     * @param string $file The file path.
     *
     * @see loadFromFile()
     */
    private function saveToFile(array $data, string $filePath): void
    {
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            set_error_handler(static function (int $errorNumber, string $errorString) use ($directory): bool {
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

        file_put_contents($filePath, "<?php\n\nreturn " . VarDumper::create($data)->export() . ";\n", LOCK_EX);
        $this->invalidateScriptCache($filePath);
    }

    /**
     * Invalidates precompiled script cache (such as OPCache) for the given file.
     *
     * @param string $file The file path.
     */
    private function invalidateScriptCache(string $file): void
    {
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
        }
    }

    private function initFileProperties(
        string $directory,
        string $filename,
        ?callable $getFileUpdatedAt,
        bool $enableConcurrencyHandling,
    ): void
    {
        $this->filePath = $directory . DIRECTORY_SEPARATOR . $filename;
        $this->getFileUpdatedAt = $getFileUpdatedAt ?? static fn (string $filename): int|false => @filemtime($filename);
        $this->enableConcurrencyHandling = $enableConcurrencyHandling;
        $this->previousEnableConcurrencyHandling = $enableConcurrencyHandling;
    }

    private function getFileUpdatedAt(): int
    {
        $getFileUpdatedAt = $this->getFileUpdatedAt;
        $fileUpdatedAt = $getFileUpdatedAt($this->filePath);
        if (!is_int($fileUpdatedAt)) {
            throw new RuntimeException('getFileUpdatedAt callable must return a UNIX timestamp.');
        }

        return $fileUpdatedAt;
    }

    private function reload(): void
    {
        $this->enableConcurrencyHandling = $this->previousEnableConcurrencyHandling;
        if (!$this->enableConcurrencyHandling) {
            return;
        }

        try {
            $fileUpdatedAt = $this->getFileUpdatedAt();
        } catch (RuntimeException) {
            return;
        }

        if ($this->currentFileUpdatedAt === $fileUpdatedAt) {
            return;
        }

        $this->load();
        $this->enableConcurrencyHandling = false;
        $this->previousEnableConcurrencyHandling = $this->enableConcurrencyHandling;
    }
}

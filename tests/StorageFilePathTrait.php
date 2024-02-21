<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use Yiisoft\Files\FileHelper;

trait StorageFilePathTrait
{
    private ?string $dataPath = null;

    private function getStoragesDirectory(): string
    {
        if ($this->dataPath === null) {
            $this->dataPath = sys_get_temp_dir() . '/' . str_replace('\\', '_', static::class) . uniqid('');
        }

        return $this->dataPath;
    }

    private function getItemsStorageFilePath(): string
    {
        return $this->getStoragesDirectory() . DIRECTORY_SEPARATOR . 'items.php';
    }

    private function getAssignmentsStorageFilePath(): string
    {
        return $this->getStoragesDirectory() . DIRECTORY_SEPARATOR . 'assignments.php';
    }

    private function clearStoragesFiles(): void
    {
        FileHelper::removeDirectory($this->getStoragesDirectory());
    }
}

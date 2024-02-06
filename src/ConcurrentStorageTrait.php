<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php;

use RuntimeException;

trait ConcurrentStorageTrait
{
    private ?int $currentFileUpdatedAt = null;

    public function getFileUpdatedAt(): int
    {
        return $this->storage->getFileUpdatedAt();
    }

    private function loadInternal(FileStorageInterface $storage): void
    {
        try {
            $fileUpdatedAt = $storage->getFileUpdatedAt();
        } catch (RuntimeException) {
            return;
        }

        if ($this->currentFileUpdatedAt === $fileUpdatedAt) {
            return;
        }

        $storage->load();
    }
}

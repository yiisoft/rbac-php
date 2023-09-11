<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use Yiisoft\Files\FileHelper;

trait FixtureTrait
{
    private ?string $dataPath = null;

    private function getDataPath(): string
    {
        if ($this->dataPath === null) {
            $uniqueId = uniqid('', more_entropy: false);
            $this->dataPath = sys_get_temp_dir() . '/' . str_replace('\\', '_', static::class) . $uniqueId;
        }

        return $this->dataPath;
    }

    private function clearFixturesFiles(): void
    {
        FileHelper::removeDirectory($this->getDataPath());
    }
}

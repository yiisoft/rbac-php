<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php;

interface FileStorageInterface
{
    public function load(): void;
}

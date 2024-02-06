<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php;

use Yiisoft\Rbac\Assignment;
use Yiisoft\Rbac\AssignmentsStorageInterface;

final class ConcurrentAssignmentsStorageDecorator implements AssignmentsStorageInterface, FileStorageInterface
{
    use ConcurrentStorageTrait;

    /**
     * @param AssignmentsStorageInterface & FileStorageInterface $storage
     */
    public function __construct(private AssignmentsStorageInterface|FileStorageInterface $storage)
    {
    }

    public function getAll(): array
    {
        $this->load();

        return $this->storage->getAll();
    }

    public function getByUserId(string $userId): array
    {
        $this->load();

        return $this->storage->getByUserId($userId);
    }

    public function getByItemNames(array $itemNames): array
    {
        $this->load();

        return $this->storage->getByItemNames($itemNames);
    }

    public function get(string $itemName, string $userId): ?Assignment
    {
        $this->load();

        return $this->storage->get($itemName, $userId);
    }

    public function exists(string $itemName, string $userId): bool
    {
        $this->load();

        return $this->storage->exists($itemName, $userId);
    }

    public function userHasItem(string $userId, array $itemNames): bool
    {
        $this->load();

        return $this->storage->userHasItem($userId, $itemNames);
    }

    public function filterUserItemNames(string $userId, array $itemNames): array
    {
        $this->load();

        return $this->storage->filterUserItemNames($userId, $itemNames);
    }

    public function add(Assignment $assignment): void
    {
        $this->load();
        $this->storage->add($assignment);
    }

    public function hasItem(string $name): bool
    {
        $this->load();

        return $this->storage->hasItem($name);
    }

    public function renameItem(string $oldName, string $newName): void
    {
        $this->load();
        $this->storage->renameItem($oldName, $newName);
    }

    public function remove(string $itemName, string $userId): void
    {
        $this->load();
        $this->storage->remove($itemName, $userId);
    }

    public function removeByUserId(string $userId): void
    {
        $this->load();
        $this->storage->removeByUserId($userId);
    }

    public function removeByItemName(string $itemName): void
    {
        $this->load();
        $this->storage->removeByItemName($itemName);
    }

    public function clear(): void
    {
        $this->storage->clear();
    }

    public function load(): void
    {
        $this->loadInternal($this->storage);
    }

    public function getFileUpdatedAt(): int
    {
        return $this->storage->getFileUpdatedAt();
    }
}

<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php;

use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Role;

final class ConcurrentItemsStorageDecorator implements ItemsStorageInterface, FileStorageInterface
{
    use ConcurrentStorageTrait;

    /**
     * @param FileStorageInterface&ItemsStorageInterface $storage
     */
    public function __construct(private ItemsStorageInterface|FileStorageInterface $storage)
    {
    }

    public function clear(): void
    {
        $this->storage->clear();
        $this->currentFileUpdatedAt = $this->storage->getFileUpdatedAt();
    }

    public function getAll(): array
    {
        $this->load();

        return $this->storage->getAll();
    }

    public function getByNames(array $names): array
    {
        $this->load();

        return $this->storage->getByNames($names);
    }

    public function get(string $name): Permission|Role|null
    {
        $this->load();

        return $this->storage->get($name);
    }

    public function exists(string $name): bool
    {
        $this->load();

        return $this->storage->exists($name);
    }

    public function roleExists(string $name): bool
    {
        $this->load();

        return $this->storage->roleExists($name);
    }

    public function add(Permission|Role $item): void
    {
        $this->load();
        $this->storage->add($item);
        $this->currentFileUpdatedAt = $this->storage->getFileUpdatedAt();
    }

    public function update(string $name, Permission|Role $item): void
    {
        $this->load();
        $this->storage->update($name, $item);
        $this->currentFileUpdatedAt = $this->storage->getFileUpdatedAt();
    }

    public function remove(string $name): void
    {
        $this->load();
        $this->storage->remove($name);
        $this->currentFileUpdatedAt = $this->storage->getFileUpdatedAt();
    }

    public function getRoles(): array
    {
        $this->load();

        return $this->storage->getRoles();
    }

    public function getRolesByNames(array $names): array
    {
        $this->load();

        return $this->storage->getRolesByNames($names);
    }

    public function getRole(string $name): ?Role
    {
        $this->load();

        return $this->storage->getRole($name);
    }

    public function clearRoles(): void
    {
        $this->load();
        $this->storage->clearRoles();
        $this->currentFileUpdatedAt = $this->storage->getFileUpdatedAt();
    }

    public function getPermissions(): array
    {
        $this->load();

        return $this->storage->getPermissions();
    }

    public function getPermissionsByNames(array $names): array
    {
        $this->load();

        return $this->storage->getPermissionsByNames($names);
    }

    public function getPermission(string $name): ?Permission
    {
        $this->load();

        return $this->storage->getPermission($name);
    }

    public function clearPermissions(): void
    {
        $this->load();
        $this->storage->clearPermissions();
        $this->currentFileUpdatedAt = $this->storage->getFileUpdatedAt();
    }

    public function getParents(string $name): array
    {
        $this->load();

        return $this->storage->getParents($name);
    }

    public function getAccessTree(string $name): array
    {
        $this->load();

        return $this->storage->getAccessTree($name);
    }

    public function getDirectChildren(string $name): array
    {
        $this->load();

        return $this->storage->getDirectChildren($name);
    }

    public function getAllChildren(string|array $names): array
    {
        $this->load();

        return $this->storage->getAllChildren($names);
    }

    public function getAllChildRoles(string|array $names): array
    {
        $this->load();

        return $this->storage->getAllChildRoles($names);
    }

    public function getAllChildPermissions(string|array $names): array
    {
        $this->load();

        return $this->storage->getAllChildPermissions($names);
    }

    public function hasChildren(string $name): bool
    {
        $this->load();

        return $this->storage->hasChildren($name);
    }

    public function hasChild(string $parentName, string $childName): bool
    {
        $this->load();

        return $this->storage->hasChild($parentName, $childName);
    }

    public function hasDirectChild(string $parentName, string $childName): bool
    {
        $this->load();

        return $this->storage->hasDirectChild($parentName, $childName);
    }

    public function addChild(string $parentName, string $childName): void
    {
        $this->load();
        $this->storage->addChild($parentName, $childName);
        $this->currentFileUpdatedAt = $this->storage->getFileUpdatedAt();
    }

    public function removeChild(string $parentName, string $childName): void
    {
        $this->load();
        $this->storage->removeChild($parentName, $childName);
        $this->currentFileUpdatedAt = $this->storage->getFileUpdatedAt();
    }

    public function removeChildren(string $parentName): void
    {
        $this->load();
        $this->storage->removeChildren($parentName);
        $this->currentFileUpdatedAt = $this->storage->getFileUpdatedAt();
    }

    public function load(): void
    {
        $this->loadInternal($this->storage);
    }
}

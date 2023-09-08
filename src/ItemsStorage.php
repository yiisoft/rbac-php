<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php;

use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Role;
use Yiisoft\Rbac\ItemsStorageInterface;

/**
 * Storage stores roles and permissions in PHP file specified by `itemFile`.
 *
 * It is suitable for authorization data that is not too big (for example, the authorization data for a personal blog
 * system).
 */
final class ItemsStorage extends CommonStorage implements ItemsStorageInterface
{
    /**
     * @var string The path of the PHP script that contains the authorization items.
     *
     * @see loadFromFile()
     * @see saveToFile()
     */
    private string $itemFile;

    /**
     * @var Item[]
     * @psalm-var array<string, Item>
     * Format is [itemName => item].
     */
    private array $items = [];

    /**
     * @var array
     * @psalm-var array<string, array<string, Item>>
     * Format is [itemName => [childName => child]].
     */
    private array $children = [];

    /**
     * @param string $directory Base directory to append to `$itemFile`.
     * @param string $itemFile The path of the PHP script that contains the authorization items. Make sure this file is
     * writable by the Web server process if the authorization needs to be changed online.
     */
    public function __construct(string $directory, string $itemFile = 'items.php')
    {
        $this->itemFile = $directory . DIRECTORY_SEPARATOR . $itemFile;
        $this->load();
    }

    /**
     * @return Item[]
     * @psalm-return array<string, Item>
     */
    public function getAll(): array
    {
        return $this->items;
    }

    public function get(string $name): ?Item
    {
        return $this->items[$name] ?? null;
    }

    public function exists(string $name): bool
    {
        return array_key_exists($name, $this->items);
    }

    public function roleExists(string $name): bool
    {
        return isset($this->getItemsByType(Item::TYPE_ROLE)[$name]);
    }

    public function add(Item $item): void
    {
        $this->items[$item->getName()] = $item;
        $this->save();
    }

    public function getRole(string $name): ?Role
    {
        return $this->getItemsByType(Item::TYPE_ROLE)[$name] ?? null;
    }

    public function getRoles(): array
    {
        return $this->getItemsByType(Item::TYPE_ROLE);
    }

    public function getRolesByNames(array $names): array
    {
        /** @psalm-var array<string, Role> */
        return array_filter(
            $this->getAll(),
            static fn (Item $item): bool => $item->getType() === Item::TYPE_ROLE && in_array($item->getName(), $names),
        );
    }

    public function getPermission(string $name): ?Permission
    {
        return $this->getItemsByType(Item::TYPE_PERMISSION)[$name] ?? null;
    }

    public function getPermissions(): array
    {
        return $this->getItemsByType(Item::TYPE_PERMISSION);
    }

    public function getPermissionsByNames(array $names): array
    {
        $permissionType = Item::TYPE_PERMISSION;

        /** @psalm-var array<string, Permission> */
        return array_filter(
            $this->getAll(),
            static fn (Item $item): bool => $item->getType() === $permissionType && in_array($item->getName(), $names),
        );
    }

    public function getParents(string $name): array
    {
        $result = [];
        $this->fillParentsRecursive($name, $result);

        return $result;
    }

    public function getDirectChildren(string $name): array
    {
        return $this->children[$name] ?? [];
    }

    public function getAllChildren(string $name): array
    {
        $result = [];
        $this->fillChildrenRecursive($name, $result);

        return $result;
    }

    public function getAllChildRoles(string $name): array
    {
        $result = [];
        $this->fillChildrenRecursive($name, $result);

        return $this->filterRoles($result);
    }

    public function getAllChildPermissions(string $name): array
    {
        $result = [];
        $this->fillChildrenRecursive($name, $result);

        return $this->filterPermissions($result);
    }

    public function addChild(string $parentName, string $childName): void
    {
        $this->children[$parentName][$childName] = $this->items[$childName];
        $this->save();
    }

    public function hasChildren(string $name): bool
    {
        return isset($this->children[$name]);
    }

    public function hasChild(string $parentName, string $childName): bool
    {
        return $this->hasLoop($childName, $parentName);
    }

    public function hasDirectChild(string $parentName, string $childName): bool
    {
        return isset($this->children[$parentName][$childName]);
    }

    public function removeChild(string $parentName, string $childName): void
    {
        if (!$this->hasDirectChild($parentName, $childName)) {
            return;
        }

        unset($this->children[$parentName][$childName]);
        $this->save();
    }

    public function removeChildren(string $parentName): void
    {
        if (!$this->hasChildren($parentName)) {
            return;
        }

        unset($this->children[$parentName]);
        $this->save();
    }

    public function remove(string $name): void
    {
        $this->clearChildrenFromItem($name);
        $this->removeItemByName($name);
        $this->save();
    }

    public function update(string $name, Item $item): void
    {
        if ($item->getName() !== $name) {
            $this->updateItemName($name, $item);
            $this->removeItemByName($name);
        }

        $this->add($item);
    }

    public function clear(): void
    {
        $this->clearLoadedData();
        $this->save();
    }

    public function clearPermissions(): void
    {
        $this->removeAllItems(Item::TYPE_PERMISSION);
    }

    public function clearRoles(): void
    {
        $this->removeAllItems(Item::TYPE_ROLE);
    }

    private function updateItemName(string $name, Item $item): void
    {
        $this->updateChildrenForItemName($name, $item);
    }

    /**
     * Saves authorization data into persistent storage.
     */
    private function save(): void
    {
        $items = [];
        foreach ($this->getAll() as $name => $item) {
            $items[$name] = array_filter($item->getAttributes());
            if ($this->hasChildren($name)) {
                foreach ($this->getDirectChildren($name) as $child) {
                    $items[$name]['children'][] = $child->getName();
                }
            }
        }
        $this->saveToFile($items, $this->itemFile);
    }

    /**
     * Loads authorization data from persistent storage.
     */
    private function load(): void
    {
        $this->clearLoadedData();
        $this->loadItems();
    }

    private function loadItems(): void
    {
        /**
         * @psalm-var array<
         *     string,
         *     array{
         *         type: string,
         *         name: string,
         *         description?: string,
         *         ruleName?: string,
         *         children?: string[]
         *     }
         * > $items
         */
        $items = $this->loadFromFile($this->itemFile);
        $itemsMtime = @filemtime($this->itemFile);
        foreach ($items as $name => $item) {
            $this->items[$name] = $this
                ->getInstanceFromAttributes($item)
                ->withCreatedAt($itemsMtime)
                ->withUpdatedAt($itemsMtime);
        }

        foreach ($items as $name => $item) {
            if (isset($item['children'])) {
                foreach ($item['children'] as $childName) {
                    if ($this->hasItem($childName)) {
                        $this->children[$name][$childName] = $this->items[$childName];
                    }
                }
            }
        }
    }

    private function clearLoadedData(): void
    {
        $this->children = [];
        $this->items = [];
    }

    private function hasItem(string $name): bool
    {
        return isset($this->items[$name]);
    }

    /**
     * @psalm-param Item::TYPE_* $type
     *
     * @return Item[]
     * @psalm-return ($type is Item::TYPE_PERMISSION ? array<string, Permission> : array<string, Role>)
     */
    private function getItemsByType(string $type): array
    {
        /** @psalm-var array<string, Permission> | array<string, Role> */
        return array_filter(
            $this->getAll(),
            static fn (Item $item): bool => $item->getType() === $type,
        );
    }

    /**
     * Removes all auth items of the specified type.
     *
     * @param string $type The auth item type (either {@see Item::TYPE_PERMISSION} or {@see Item::TYPE_ROLE}).
     * @psalm-param Item::TYPE_* $type
     */
    private function removeAllItems(string $type): void
    {
        foreach ($this->getItemsByType($type) as $item) {
            $this->remove($item->getName());
        }
    }

    private function clearChildrenFromItem(string $itemName): void
    {
        unset($this->children[$itemName]);
    }

    private function getInstanceByTypeAndName(string $type, string $name): Item
    {
        return $type === Item::TYPE_PERMISSION ? new Permission($name) : new Role($name);
    }

    /**
     * @psalm-param array{type: string, name: string, description?: string, ruleName?: string} $attributes
     */
    private function getInstanceFromAttributes(array $attributes): Item
    {
        return $this
            ->getInstanceByTypeAndName($attributes['type'], $attributes['name'])
            ->withDescription($attributes['description'] ?? '')
            ->withRuleName($attributes['ruleName'] ?? null);
    }

    private function updateChildrenForItemName(string $name, Item $item): void
    {
        if ($this->hasChildren($name)) {
            $this->children[$item->getName()] = $this->children[$name];
            unset($this->children[$name]);
        }
        foreach ($this->children as &$children) {
            if (isset($children[$name])) {
                $children[$item->getName()] = $item;
                unset($children[$name]);
            }
        }
    }

    private function removeItemByName(string $name): void
    {
        unset($this->items[$name]);
    }

    /**
     * @psalm-param array<string, Item> $result
     * @psalm-param-out array<string, Item> $result
     */
    private function fillParentsRecursive(string $name, array &$result): void
    {
        foreach ($this->children as $parentName => $childItems) {
            foreach ($childItems as $childItem) {
                if ($childItem->getName() === $name) {
                    continue;
                }

                $parent = $this->get($parentName);
                if ($parent !== null) {
                    $result[$parentName] = $parent;
                }

                $this->fillParentsRecursive($parentName, $result);

                break;
            }
        }
    }

    /**
     * @psalm-param array<string, Item> $result
     * @psalm-param-out array<string, Item> $result
     */
    private function fillChildrenRecursive(string $name, array &$result): void
    {
        $children = $this->children[$name] ?? [];
        foreach ($children as $childName => $_childItem) {
            $child = $this->get($childName);
            if ($child !== null) {
                $result[$childName] = $child;
            }

            $this->fillChildrenRecursive($childName, $result);
        }
    }

    /**
     * @param Item[] $array
     * @psalm-param array<string, Item> $array
     *
     * @return Role[]
     * @psalm-return array<string, Role>
     */
    private function filterRoles(array $array): array
    {
        return array_filter(
            $this->getRoles(),
            static fn (Role $roleItem): bool => array_key_exists($roleItem->getName(), $array),
        );
    }

    /**
     * @param Item[] $items
     * @psalm-param array<string, Item> $items
     *
     * @return Permission[]
     * @psalm-return array<string, Permission>
     */
    private function filterPermissions(array $items): array
    {
        $permissions = [];
        foreach (array_keys($items) as $permissionName) {
            $permission = $this->getPermission($permissionName);
            if ($permission !== null) {
                $permissions[$permissionName] = $permission;
            }
        }

        return $permissions;
    }

    /**
     * Checks whether there is a loop in the item hierarchy.
     *
     * @param string $parentName Name of the parent item.
     * @param string $childName Name of the child item that is to be added to the hierarchy.
     *
     * @return bool Whether a loop exists.
     */
    private function hasLoop(string $parentName, string $childName): bool
    {
        if ($parentName === $childName) {
            return true;
        }

        $children = $this->getDirectChildren($childName);
        if (empty($children)) {
            return false;
        }

        foreach ($children as $groupChild) {
            if ($this->hasLoop($parentName, $groupChild->getName())) {
                return true;
            }
        }

        return false;
    }
}

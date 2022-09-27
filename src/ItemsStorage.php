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
     * @psalm-return array<string,Item>
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

    public function getPermission(string $name): ?Permission
    {
        return $this->getItemsByType(Item::TYPE_PERMISSION)[$name] ?? null;
    }

    public function getPermissions(): array
    {
        return $this->getItemsByType(Item::TYPE_PERMISSION);
    }

    public function getParents(string $name): array
    {
        $result = [];
        $this->getParentsRecursive($name, $result);
        return $result;
    }

    public function getChildren(string $name): array
    {
        return $this->children[$name] ?? [];
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

    public function removeChild(string $parentName, string $childName): void
    {
        unset($this->children[$parentName][$childName]);
        $this->save();
    }

    public function removeChildren(string $parentName): void
    {
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
                foreach ($this->getChildren($name) as $child) {
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
     * @return Item[]
     * @psalm-return array<
     *     array-key,
     *     ($type is Item::TYPE_PERMISSION ? Permission : ($type is Item::TYPE_ROLE ? Role : Item))
     * >
     */
    private function getItemsByType(string $type): array
    {
        return $this->filterItems(
            fn (Item $item) => $item->getType() === $type
        );
    }

    /**
     * @psalm-param callable(mixed, mixed=):scalar $callback
     *
     * @return Item[]
     */
    private function filterItems(callable $callback): array
    {
        return array_filter($this->getAll(), $callback);
    }

    /**
     * Removes all auth items of the specified type.
     *
     * @param string $type The auth item type (either {@see Item::TYPE_PERMISSION} or {@see Item::TYPE_ROLE}).
     */
    private function removeAllItems(string $type): void
    {
        foreach ($this->getItemsByType($type) as $item) {
            $this->remove($item->getName());
        }
    }

    private function clearChildrenFromItem(string $itemName): void
    {
        foreach ($this->children as &$children) {
            unset($children[$itemName]);
        }
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
     * @psalm-param array<string,Item> $result
     */
    private function getParentsRecursive(string $name, array &$result): void
    {
        foreach ($this->children as $parentName => $items) {
            foreach ($items as $item) {
                if ($item->getName() === $name) {
                    $result[$parentName] = $this->items[$name];
                    $this->getParentsRecursive($parentName, $result);
                    break;
                }
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php;

use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Role;
use Yiisoft\Rbac\SimpleItemsStorage;

/**
 * Storage stores roles and permissions in PHP file specified in {@see ItemsStorage::$itemFile}.
 *
 * It is suitable for authorization data that is not too big (for example, the authorization data for a personal blog
 * system).
 *
 * @psalm-import-type RawItem from SimpleItemsStorage
 */
final class ItemsStorage extends SimpleItemsStorage
{
    use FileStorageTrait;

    /**
     * @var string The path of the PHP script that contains the authorization items.
     *
     * @see loadFromFile()
     * @see saveToFile()
     */
    private string $itemFile;

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

    public function add(Permission|Role $item): void
    {
        parent::add($item);

        $this->save();
    }

    public function addChild(string $parentName, string $childName): void
    {
        parent::addChild($parentName, $childName);

        $this->save();
    }

    public function removeChild(string $parentName, string $childName): void
    {
        if (!$this->hasDirectChild($parentName, $childName)) {
            return;
        }

        parent::removeChild($parentName, $childName);

        $this->save();
    }

    public function removeChildren(string $parentName): void
    {
        if (!$this->hasChildren($parentName)) {
            return;
        }

        parent::removeChildren($parentName);

        $this->save();
    }

    public function remove(string $name): void
    {
        parent::remove($name);

        $this->save();
    }

    public function clear(): void
    {
        parent::clear();

        $this->save();
    }

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

    private function load(): void
    {
        parent::clear();

        $this->loadItems();
    }

    private function loadItems(): void
    {
        /** @psalm-var array<string, RawItem> $items */
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

    private function hasItem(string $name): bool
    {
        return isset($this->items[$name]);
    }

    private function getInstanceByTypeAndName(string $type, string $name): Permission|Role
    {
        return $type === Item::TYPE_PERMISSION ? new Permission($name) : new Role($name);
    }

    /**
     * @psalm-param RawItem $attributes
     */
    private function getInstanceFromAttributes(array $attributes): Permission|Role
    {
        $item = $this->getInstanceByTypeAndName($attributes['type'], $attributes['name']);

        $description = $attributes['description'] ?? null;
        if ($description !== null) {
            $item = $item->withDescription($description);
        }

        $ruleName = $attributes['ruleName'] ?? null;
        if ($ruleName !== null) {
            $item = $item->withRuleName($ruleName);
        }

        return $item;
    }
}

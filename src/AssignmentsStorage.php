<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php;

use Yiisoft\Rbac\Assignment;
use Yiisoft\Rbac\SimpleAssignmentsStorage;

/**
 * Storage stores roles and permissions in PHP file specified in {@see AssignmentsStorage::$assignmentFile}.
 *
 * It is suitable for authorization data that is not too big (for example, the authorization data for a personal blog
 * system).
 *
 * @psalm-import-type RawAssignment from SimpleAssignmentsStorage
 */
final class AssignmentsStorage extends SimpleAssignmentsStorage
{
    use FileStorageTrait;

    public function __construct(
        string $directory,
        string $assignmentFile = 'assignments.php',
        ?callable $getFileUpdatedAt = null,
        bool $enableConcurrencyHandling = false,
    ) {
        $this->initFileProperties($directory, $assignmentFile, $getFileUpdatedAt, $enableConcurrencyHandling);
        $this->load();
    }

    public function getAll(): array
    {
        $this->reload();

        return parent::getAll();
    }

    public function getByUserId(string $userId): array
    {
        $this->reload();

        return parent::getByUserId($userId);
    }

    public function getByItemNames(array $itemNames): array
    {
        $this->reload();

        return parent::getByItemNames($itemNames);
    }

    public function add(Assignment $assignment): void
    {
        parent::add($assignment);

        $this->save();
    }

    public function hasItem(string $name): bool
    {
        $this->reload();

        return parent::hasItem($name);
    }

    public function renameItem(string $oldName, string $newName): void
    {
        if ($oldName === $newName) {
            return;
        }

        parent::renameItem($oldName, $newName);
        $this->save();
    }

    public function remove(string $itemName, string $userId): void
    {
        if (!$this->exists($itemName, $userId)) {
            return;
        }

        parent::remove($itemName, $userId);

        $this->save();
    }

    public function removeByUserId(string $userId): void
    {
        parent::removeByUserId($userId);

        $this->save();
    }

    public function removeByItemName(string $itemName): void
    {
        parent::removeByItemName($itemName);

        $this->save();
    }

    public function clear(): void
    {
        parent::clear();

        $this->save();
    }

    private function load(): void
    {
        parent::clear();

        /** @psalm-var list<RawAssignment> $assignments */
        $assignments = $this->loadFromFile($this->filePath);
        if (empty($assignments)) {
            return;
        }

        $fileUpdatedAt = $this->getFileUpdatedAt();
        foreach ($assignments as $assignment) {
            /** @psalm-suppress InvalidPropertyAssignmentValue */
            $this->assignments[$assignment['user_id']][$assignment['item_name']] = new Assignment(
                userId: $assignment['user_id'],
                itemName: $assignment['item_name'],
                createdAt: $assignment['created_at'] ?? $fileUpdatedAt,
            );
        }
    }

    private function save(): void
    {
        $assignmentData = [];
        foreach ($this->assignments as $userAssignments) {
            foreach ($userAssignments as $userAssignment) {
                $assignmentData[] = $userAssignment->getAttributes();
            }
        }

        $this->saveToFile($assignmentData, $this->filePath);
    }

    private function reload(): void
    {
    }
}

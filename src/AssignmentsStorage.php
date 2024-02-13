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
final class AssignmentsStorage extends SimpleAssignmentsStorage implements FileStorageInterface
{
    use FileStorageTrait;

    public function __construct(
        string $directory,
        string $fileName = 'assignments.php',
        ?callable $getFileUpdatedAt = null,
    ) {
        $this->initFileProperties($directory, $fileName, $getFileUpdatedAt);
        $this->load();
    }

    public function add(Assignment $assignment): void
    {
        parent::add($assignment);
        $this->save();
    }

    public function renameItem(string $oldName, string $newName): void
    {
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

    public function load(): void
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

        $this->saveToFile($assignmentData);
    }
}

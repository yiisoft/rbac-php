<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php;

use Yiisoft\Rbac\Assignment;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Item;

/**
 * Storage stores authorization data in three PHP files specified by {@see Storage::itemFile},
 * {@see Storage::assignmentFile} and {@see Storage::ruleFile}.
 *
 * It is suitable for authorization data that is not too big (for example, the authorization data for
 * a personal blog system).
 */
final class AssignmentsStorage extends CommonStorage implements AssignmentsStorageInterface
{
    /**
     * @var string The path of the PHP script that contains the authorization assignments.
     * This can be either a file path or a [path alias](guide:concept-aliases) to the file.
     * Make sure this file is writable by the Web server process if the authorization needs to be changed
     * online.
     *
     * @see loadFromFile()
     * @see saveToFile()
     */
    private string $assignmentFile;

    /**
     * @var array
     * @psalm-var array<string, array<string, Assignment>>
     * Format is [userId => [itemName => assignment]].
     */
    private array $assignments = [];

    public function __construct(
        string $directory,
        string $assignmentFile = 'assignments.php'
    ) {
        $this->assignmentFile = $directory . DIRECTORY_SEPARATOR . $assignmentFile;
        $this->loadAssignments();
    }

    public function getAll(): array
    {
        return $this->assignments;
    }

    public function getUserAssignments(string $userId): array
    {
        return $this->assignments[$userId] ?? [];
    }

    public function get(string $userId, string $name): ?Assignment
    {
        return $this->getUserAssignments($userId)[$name] ?? null;
    }

    public function add(string $userId, string $itemName): void
    {
        $this->assignments[$userId][$itemName] = new Assignment($userId, $itemName, time());
        $this->saveAssignments();
    }

    public function hasItem(string $name): bool
    {
        foreach ($this->getAll() as $assignmentInfo) {
            if (array_key_exists($name, $assignmentInfo)) {
                return true;
            }
        }
        return false;
    }

    public function renameItem(string $oldName, string $newName): void
    {
        if ($oldName === $newName) {
            return;
        }

        foreach ($this->assignments as &$assignments) {
            if (isset($assignments[$oldName])) {
                $assignments[$newName] = $assignments[$oldName]->withItemName($newName);
                unset($assignments[$oldName]);
            }
        }

        $this->saveAssignments();
    }

    public function remove(string $userId, string $itemName): void
    {
        unset($this->assignments[$userId][$itemName]);
        $this->saveAssignments();
    }

    public function removeUserAssignments(string $userId): void
    {
        $this->assignments[$userId] = [];
        $this->saveAssignments();
    }

    public function removeItemAssignments(string $itemName): void
    {
        foreach ($this->assignments as &$assignments) {
            unset($assignments[$itemName]);
        }
        $this->saveAssignments();
    }

    public function clear(): void
    {
        $this->assignments = [];
        $this->saveAssignments();
    }

    /**
     * Loads authorization data from persistent storage.
     */
    private function loadAssignments(): void
    {
        /**
         * @psalm-var array<string|int,string[]> $assignments
         */
        $assignments = $this->loadFromFile($this->assignmentFile);
        $modifiedTime = @filemtime($this->assignmentFile);
        foreach ($assignments as $userId => $roles) {
            foreach ($roles as $role) {
                $this->assignments[$userId][$role] = new Assignment((string)$userId, $role, $modifiedTime);
            }
        }
    }

    /**
     * Saves assignments data into persistent storage.
     */
    private function saveAssignments(): void
    {
        $assignmentData = [];
        foreach ($this->assignments as $userId => $assignments) {
            foreach ($assignments as $assignment) {
                $assignmentData[$userId][] = $assignment->getItemName();
            }
        }
        $this->saveToFile($assignmentData, $this->assignmentFile);
    }
}

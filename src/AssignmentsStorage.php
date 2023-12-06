<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php;

use Yiisoft\Rbac\Assignment;
use Yiisoft\Rbac\AssignmentsStorageInterface;

use function array_key_exists;

/**
 * Storage stores authorization data in three PHP files specified by {@see Storage::itemFile},
 * {@see Storage::assignmentFile} and {@see Storage::ruleFile}.
 *
 * It is suitable for authorization data that is not too big (for example, the authorization data for a personal blog
 * system).
 */
final class AssignmentsStorage extends CommonStorage implements AssignmentsStorageInterface
{
    /**
     * @var string The path of the PHP script that contains the authorization assignments. Make sure this file is
     * writable by the web server process if the authorization needs to be changed online.
     *
     * @see loadFromFile()
     * @see saveToFile()
     */
    private string $assignmentFile;

    /**
     * @var array Array in format is `[userId => [itemName => assignment]]`.
     * @psalm-var array<string, array<string, Assignment>>
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

    public function getByUserId(string $userId): array
    {
        return $this->assignments[$userId] ?? [];
    }

    public function getByItemNames(array $itemNames): array
    {
        $result = [];

        foreach ($this->assignments as $assignments) {
            foreach ($assignments as $userAssignment) {
                if (in_array($userAssignment->getItemName(), $itemNames, true)) {
                    $result[] = $userAssignment;
                }
            }
        }

        return $result;
    }

    public function get(string $itemName, string $userId): ?Assignment
    {
        return $this->getByUserId($userId)[$itemName] ?? null;
    }

    public function exists(string $itemName, string $userId): bool
    {
        return isset($this->getByUserId($userId)[$itemName]);
    }

    public function userHasItem(string $userId, array $itemNames): bool
    {
        $assignments = $this->getByUserId($userId);
        if (empty($assignments)) {
            return false;
        }

        foreach ($itemNames as $itemName) {
            if (array_key_exists($itemName, $assignments)) {
                return true;
            }
        }

        return false;
    }

    public function filterUserItemNames(string $userId, array $itemNames): array
    {
        $assignments = $this->getByUserId($userId);
        if (empty($assignments)) {
            return [];
        }

        $userItemNames = [];
        foreach ($itemNames as $itemName) {
            if (array_key_exists($itemName, $assignments)) {
                $userItemNames[] = $itemName;
            }
        }

        return $userItemNames;
    }

    public function add(Assignment $assignment): void
    {
        $this->assignments[$assignment->getUserId()][$assignment->getItemName()] = $assignment;
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

    public function remove(string $itemName, string $userId): void
    {
        if (!$this->exists($itemName, $userId)) {
            return;
        }

        unset($this->assignments[$userId][$itemName]);
        $this->saveAssignments();
    }

    public function removeByUserId(string $userId): void
    {
        $this->assignments[$userId] = [];
        $this->saveAssignments();
    }

    public function removeByItemName(string $itemName): void
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
                /** @psalm-suppress InvalidPropertyAssignmentValue */
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

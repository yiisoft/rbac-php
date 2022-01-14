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
 *
 * @package Yiisoft\Rbac\Php
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
        $this->load();
    }

    public function getAssignments(): array
    {
        return $this->assignments;
    }

    public function getUserAssignments(string $userId): array
    {
        return $this->assignments[$userId] ?? [];
    }

    public function getUserAssignmentByName(string $userId, string $name): ?Assignment
    {
        return $this->getUserAssignments($userId)[$name] ?? null;
    }

    public function addAssignment(string $userId, Item $item): void
    {
        $this->assignments[$userId][$item->getName()] = new Assignment($userId, $item->getName(), time());
        $this->saveAssignments();
    }

    public function assignmentExist(string $name): bool
    {
        foreach ($this->getAssignments() as $assignmentInfo) {
            foreach ($assignmentInfo as $itemName => $_assignment) {
                if ($itemName === $name) {
                    return true;
                }
            }
        }
        return false;
    }

    public function updateAssignmentsForItemName(string $name, Item $item): void
    {
        if ($name === $item->getName()) {
            return;
        }

        foreach ($this->assignments as &$assignments) {
            if (isset($assignments[$name])) {
                $assignments[$item->getName()] = $assignments[$name]->withItemName($item->getName());
                unset($assignments[$name]);
            }
        }

        $this->saveAssignments();
    }

    public function removeAssignment(string $userId, Item $item): void
    {
        unset($this->assignments[$userId][$item->getName()]);
        $this->saveAssignments();
    }

    public function removeAllAssignments(string $userId): void
    {
        $this->assignments[$userId] = [];
        $this->saveAssignments();
    }

    public function removeAssignmentsFromItem(Item $item): void
    {
        $this->clearAssignmentsFromItem($item);
        $this->saveAssignments();
    }

    public function clearAssignments(): void
    {
        $this->assignments = [];
        $this->saveAssignments();
    }

    /**
     * Loads authorization data from persistent storage.
     */
    private function load(): void
    {
        $this->loadAssignments();
    }

    private function loadAssignments(): void
    {
        /** @psalm-var array<string,string[]> $assignments */
        $assignments = $this->loadFromFile($this->assignmentFile);
        $assignmentsMtime = @filemtime($this->assignmentFile);
        foreach ($assignments as $userId => $roles) {
            foreach ($roles as $role) {
                $this->assignments[$userId][$role] = new Assignment($userId, $role, $assignmentsMtime);
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

    private function clearAssignmentsFromItem(Item $item): void
    {
        foreach ($this->assignments as &$assignments) {
            unset($assignments[$item->getName()]);
        }
    }
}

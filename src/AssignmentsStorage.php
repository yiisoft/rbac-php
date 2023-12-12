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
 */
final class AssignmentsStorage extends SimpleAssignmentsStorage
{
    use FileStorageTrait;

    /**
     * @var string The path of the PHP script that contains the authorization assignments. Make sure this file is
     * writable by the web server process if the authorization needs to be changed online.
     *
     * @see loadFromFile()
     * @see saveToFile()
     */
    private string $assignmentFile;

    public function __construct(
        string $directory,
        string $assignmentFile = 'assignments.php'
    ) {
        $this->assignmentFile = $directory . DIRECTORY_SEPARATOR . $assignmentFile;
        $this->loadAssignments();
    }

    public function add(Assignment $assignment): void
    {
        parent::add($assignment);

        $this->saveAssignments();
    }

    public function renameItem(string $oldName, string $newName): void
    {
        if ($oldName === $newName) {
            return;
        }

        parent::renameItem($oldName, $newName);

        $this->saveAssignments();
    }

    public function remove(string $itemName, string $userId): void
    {
        if (!$this->exists($itemName, $userId)) {
            return;
        }

        parent::remove($itemName, $userId);

        $this->saveAssignments();
    }

    public function removeByUserId(string $userId): void
    {
        parent::removeByUserId($userId);

        $this->saveAssignments();
    }

    public function removeByItemName(string $itemName): void
    {
        parent::removeByItemName($itemName);

        $this->saveAssignments();
    }

    public function clear(): void
    {
        parent::clear();

        $this->saveAssignments();
    }

    private function loadAssignments(): void
    {
        /** @psalm-var array<string|int, string[]> $assignments */
        $assignments = $this->loadFromFile($this->assignmentFile);
        $modifiedTime = @filemtime($this->assignmentFile);
        foreach ($assignments as $userId => $roles) {
            foreach ($roles as $role) {
                /** @psalm-suppress InvalidPropertyAssignmentValue */
                $this->assignments[$userId][$role] = new Assignment((string)$userId, $role, $modifiedTime);
            }
        }
    }

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

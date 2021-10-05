<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php;

use Yiisoft\Rbac\Assignment;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Role;
use Yiisoft\Rbac\Rule;
use Yiisoft\Rbac\StorageInterface;
use Yiisoft\VarDumper\VarDumper;

/**
 * Storage stores authorization data in three PHP files specified by {@see Storage::itemFile},
 * {@see Storage::assignmentFile} and {@see Storage::ruleFile}.
 *
 * It is suitable for authorization data that is not too big (for example, the authorization data for
 * a personal blog system).
 *
 * @package Yiisoft\Rbac\Php
 */
final class AssignmentsStorage implements AssignmentsStorageInterface
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
            foreach ($assignmentInfo as $itemName => $assignment) {
                if ($itemName === $name) {
                    return true;
                }
            }
        }
        return false;
    }

    public function updateAssignmentsForItemName(string $name, Item $item): void
    {
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
        $assignments = $this->loadFromFile($this->assignmentFile);
        $assignmentsMtime = @filemtime($this->assignmentFile);
        foreach ($assignments as $userId => $roles) {
            foreach ($roles as $role) {
                $this->assignments[$userId][$role] = new Assignment((string)$userId, $role, $assignmentsMtime);
            }
        }
    }

    /**
     * Loads the authorization data from a PHP script file.
     *
     * @param string $file The file path.
     *
     * @return array The authorization data.
     *
     * @see saveToFile()
     */
    private function loadFromFile(string $file): array
    {
        if (is_file($file)) {
            /**
             * @psalm-suppress UnresolvableInclude
             */
            return require $file;
        }

        return [];
    }

    /**
     * Saves the authorization data to a PHP script file.
     *
     * @param array $data The authorization data
     * @param string $file The file path.
     *
     * @see loadFromFile()
     */
    private function saveToFile(array $data, string $file): void
    {
        if (!file_exists(dirname($file)) && !mkdir($concurrentDirectory = dirname($file)) && !is_dir(
            $concurrentDirectory
        )) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        file_put_contents($file, "<?php\n\nreturn " . VarDumper::create($data)->export() . ";\n", LOCK_EX);
        $this->invalidateScriptCache($file);
    }

    /**
     * Invalidates precompiled script cache (such as OPCache) for the given file.
     *
     * @param string $file The file path.
     */
    private function invalidateScriptCache(string $file): void
    {
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
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
                /* @var $assignment Assignment */
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

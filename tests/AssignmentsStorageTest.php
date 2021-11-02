<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Rbac\Assignment;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Php\AssignmentsStorage;
use Yiisoft\Rbac\Role;

/**
 * @group rbac
 */
final class AssignmentsStorageTest extends TestCase
{
    use FixtureTrait;

    public function testClearAssignments(): void
    {
        $storage = $this->createStorage();
        $storage->clearAssignments();
        $this->assertCount(0, $this->createStorage()->getAssignments());
    }

    public function testGetAssignments(): void
    {
        $storage = $this->createStorage();
        $this->assertEquals(
            [
                'reader A',
                'author B',
                'admin C',
            ],
            array_keys($storage->getAssignments())
        );
    }

    public function testGetUserAssignments(): void
    {
        $storage = $this->createStorage();
        $this->assertEquals(
            [
                'author',
                'deletePost',
            ],
            array_keys($storage->getUserAssignments('author B'))
        );
        $this->assertEmpty($storage->getUserAssignments('unknown user'));
    }

    public function testGetUserAssignmentByName(): void
    {
        $storage = $this->createStorage();
        $this->assertInstanceOf(
            Assignment::class,
            $storage->getUserAssignmentByName('author B', 'author')
        );

        $this->assertNull($storage->getUserAssignmentByName('author B', 'nonExistAssigment'));
    }

    public function testAddAssignment(): void
    {
        $storage = $this->createStorage();
        $role = new Role('author');

        $storage->addAssignment('reader A', $role);
        $this->assertEquals(
            [
                'Fast Metabolism',
                'reader',
                'author',
            ],
            array_keys($storage->getUserAssignments('reader A'))
        );
    }

    public function testAssignmentExist(): void
    {
        $storage = $this->createStorage();

        $this->assertTrue($storage->assignmentExist('deletePost'));
        $this->assertFalse($storage->assignmentExist('nonExistAssignment'));
    }

    public function testAssigmentSave(): void
    {
        $storage = $this->createStorage();

        $role = new Role('author');
        $storage->addAssignment('reader A', $role);

        $storageNew = $this->createStorage();

        $this->assertEquals($storage->getAssignments(), $storageNew->getAssignments());
    }

    public function testRemoveAssignment(): void
    {
        $storage = $this->createStorage();
        $permission = new Permission('deletePost');

        $storage->removeAssignment('author B', $permission);
        $this->assertEquals(['author'], array_keys($storage->getUserAssignments('author B')));
    }

    public function testRemoveAllAssignments(): void
    {
        $storage = $this->createStorage();
        $storage->removeAllAssignments('author B');
        $this->assertEmpty($storage->getUserAssignments('author B'));
    }

    public function testRemoveAssignmentsFromItem(): void
    {
        $storage = $this->createStorage();
        $permission = new Permission('deletePost');

        $storage->removeAssignmentsFromItem($permission);

        $this->assertNull($storage->getUserAssignmentByName('author B', 'deletePost'));
    }

    public function testUpdateAssignmentsForItemNameWithoutChangeName(): void
    {
        $storage = $this->createStorage();

        $roleName = 'reader';
        $userId = 'reader A';

        $beforeAssignments = $storage->getUserAssignmentByName($userId, $roleName);

        $role = (new Role($roleName))->withDescription('new description');
        $storage->updateAssignmentsForItemName($roleName, $role);

        $afterAssignments = $storage->getUserAssignmentByName($userId, $roleName);

        $this->assertEquals($beforeAssignments, $afterAssignments);
    }

    protected function tearDown(): void
    {
        $this->clearFixturesFiles();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        $this->addFixturesFiles();
        parent::setUp();
    }

    private function createStorage(): AssignmentsStorage
    {
        return new AssignmentsStorage($this->dataPath);
    }
}

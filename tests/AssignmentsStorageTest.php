<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Rbac\Assignment;
use Yiisoft\Rbac\Php\AssignmentsStorage;

/**
 * @group rbac
 */
final class AssignmentsStorageTest extends TestCase
{
    use FixtureTrait;

    public function testClearAssignments(): void
    {
        $storage = $this->createStorage();
        $storage->clear();
        $this->assertCount(0, $this->createStorage()->getAll());
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
            array_keys($storage->getAll())
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
            array_keys($storage->getByUserId('author B'))
        );
        $this->assertEmpty($storage->getByUserId('unknown user'));
    }

    public function testGetUserAssignmentByName(): void
    {
        $storage = $this->createStorage();
        $this->assertInstanceOf(
            Assignment::class,
            $storage->get('author', 'author B')
        );

        $this->assertNull($storage->get('nonExistAssigment', 'author B'));
    }

    public function testAddAssignment(): void
    {
        $storage = $this->createStorage();

        $storage->add('author', 'reader A');
        $this->assertEquals(
            [
                'Fast Metabolism',
                'reader',
                'author',
            ],
            array_keys($storage->getByUserId('reader A'))
        );
    }

    public function testAssignmentExist(): void
    {
        $storage = $this->createStorage();

        $this->assertTrue($storage->hasItem('deletePost'));
        $this->assertFalse($storage->hasItem('nonExistAssignment'));
    }

    public function testAssigmentSave(): void
    {
        $storage = $this->createStorage();

        $storage->add('author', 'reader A');

        $storageNew = $this->createStorage();

        $this->assertEquals($storage->getAll(), $storageNew->getAll());
    }

    public function testRemoveAssignment(): void
    {
        $storage = $this->createStorage();

        $storage->remove('deletePost', 'author B');
        $this->assertEquals(['author'], array_keys($storage->getByUserId('author B')));
    }

    public function testRemoveAllAssignments(): void
    {
        $storage = $this->createStorage();
        $storage->removeByUserId('author B');
        $this->assertEmpty($storage->getByUserId('author B'));
    }

    public function testRemoveAssignmentsFromItem(): void
    {
        $storage = $this->createStorage();

        $storage->removeByItemName('deletePost');

        $this->assertNull($storage->get('deletePost', 'author B'));
    }

    public function testUpdateAssignmentsForItemNameWithoutChangeName(): void
    {
        $storage = $this->createStorage();

        $roleName = 'reader';
        $userId = 'reader A';

        $beforeAssignments = $storage->get($roleName, $userId);

        $storage->renameItem($roleName, $roleName);

        $afterAssignments = $storage->get($roleName, $userId);

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

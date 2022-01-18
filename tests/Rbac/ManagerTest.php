<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests\Rbac;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Manager;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Php\AssignmentsStorage;
use Yiisoft\Rbac\Php\RolesStorage;
use Yiisoft\Rbac\Php\Tests\AuthorRule;
use Yiisoft\Rbac\Php\Tests\EasyRule;
use Yiisoft\Rbac\Role;
use Yiisoft\Rbac\RolesStorageInterface;
use Yiisoft\Rbac\ClassNameRuleFactory;

final class ManagerTest extends TestCase
{
    protected Manager $manager;

    protected RolesStorageInterface $rolesStorage;

    protected AssignmentsStorageInterface $assignmentsStorage;

    private function getDataPath(): string
    {
        return sys_get_temp_dir() . '/' . str_replace('\\', '_', static::class) . uniqid('', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $datapath = $this->getDataPath();

        $this->rolesStorage = $this->createRolesStorage($datapath);
        $this->assignmentsStorage = $this->createAssignmentsStorage($datapath);

        $this->manager = $this->createManager($this->rolesStorage, $this->assignmentsStorage);
    }

    public function dataUserHasPermission(): array
    {
        return [
            // reader A
            [false, 'reader A', 'createPost'],
            [true, 'reader A', 'readPost'],
            [false, 'reader A', 'updatePost', ['authorID' => 'author B']],
            [false, 'reader A', 'updateAnyPost'],
            [false, 'reader A', 'reader'],
            [false, 'reader A', 'withoutChildren'],
            [false, 'reader A', 'nonExistingPermission'],

            // author B
            [true, 'author B', 'createPost'],
            [true, 'author B', 'readPost'],
            [true, 'author B', 'updatePost', ['authorID' => 'author B']],
            [false, 'author B', 'updateAnyPost'],

            // admin C
            [true, 'admin C', 'createPost'],
            [true, 'admin C', 'readPost'],
            [false, 'admin C', 'updatePost', ['authorID' => 'author B']],
            [true, 'admin C', 'updateAnyPost'],

            // non-exist-user
            [false, 'non-exist-user', 'createPost'],
            [false, 'non-exist-user', 'readPost'],
            [false, 'non-exist-user', 'updatePost', ['authorID' => 'author B']],
            [false, 'non-exist-user', 'updateAnyPost'],
            [false, 'non-exist-user', 'reader'],
            [false, 'non-exist-user', 'withoutChildren'],
            [false, 'non-exist-user', 'nonExistingPermission'],

            // guest
            [false, null, 'createPost'],
            [false, null, 'readPost'],
            [false, null, 'updatePost', ['authorID' => 'author B']],
            [false, null, 'updateAnyPost'],
            [false, null, 'reader'],
            [false, null, 'withoutChildren'],
            [false, null, 'nonExistingPermission'],
        ];
    }

    /**
     * @dataProvider dataUserHasPermission
     */
    public function testUserHasPermission(bool $expected, $user, string $permissionName, array $parameters = []): void
    {
        $this->assertSame(
            $expected,
            $this->manager->userHasPermission($user, $permissionName, $parameters),
        );
    }

    /**
     * @dataProvider dataProviderUserHasPermissionWithFailUserId
     */
    public function testUserHasPermissionWithFailUserId($userId): void
    {
        $this->expectException(InvalidArgumentException::class);

        $permission = 'createPost';
        $params = ['authorID' => 'author B'];

        $this->manager->userHasPermission($userId, $permission, $params);
    }

    public function dataProviderUserHasPermissionWithFailUserId(): array
    {
        return [
            [true],
            [['test' => 1]],
        ];
    }

    public function testUserHasPermissionReturnFalseForNonExistingUserAndNoDefaultRoles(): void
    {
        $this->manager->setDefaultRoleNames([]);
        $this->assertFalse($this->manager->userHasPermission('unknown user', 'createPost'));
    }

    public function testCanAddChildReturnTrue(): void
    {
        $this->assertTrue(
            $this->manager->canAddChild(
                new Role('author'),
                new Role('reader')
            )
        );
    }

    public function testCanAddChildDetectLoop(): void
    {
        $this->assertFalse(
            $this->manager->canAddChild(
                new Role('reader'),
                new Role('author')
            )
        );
    }

    public function testCanAddChildPermissionToRole(): void
    {
        $this->assertFalse(
            $this->manager->canAddChild(
                new Permission('test_permission'),
                new Role('test_role')
            )
        );
    }

    public function testAddChild(): void
    {
        $this->manager->addChild(
            $this->rolesStorage->getRoleByName('reader'),
            $this->rolesStorage->getPermissionByName('createPost')
        );

        $this->assertEquals(
            [
                'readPost',
                'createPost',
            ],
            array_keys($this->rolesStorage->getChildrenByName('reader'))
        );
    }

    public function testAddChildNotHasItem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Either "new reader" does not exist.');

        $this->manager->addChild(
            new Role('new reader'),
            $this->rolesStorage->getPermissionByName('createPost')
        );
    }

    public function testAddChildEqualName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add "createPost" as a child of itself.');

        $this->manager->addChild(
            new Role('createPost'),
            $this->rolesStorage->getPermissionByName('createPost')
        );
    }

    public function testAddChildPermissionToRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Can not add "reader" role as a child of "createPost" permission.');

        $this->manager->addChild(
            $this->rolesStorage->getPermissionByName('createPost'),
            $this->rolesStorage->getRoleByName('reader')
        );
    }

    public function testAddChildAlreadyAdded(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The item "reader" already has a child "readPost".');

        $this->manager->addChild(
            $this->rolesStorage->getRoleByName('reader'),
            $this->rolesStorage->getPermissionByName('readPost')
        );
    }

    public function testAddChildDetectLoop(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot add "author" as a child of "reader". A loop has been detected.');

        $this->manager->addChild(
            $this->rolesStorage->getRoleByName('reader'),
            $this->rolesStorage->getRoleByName('author'),
        );
    }

    public function testRemoveChild(): void
    {
        $this->manager->removeChild(
            $this->rolesStorage->getRoleByName('author'),
            $this->rolesStorage->getPermissionByName('createPost'),
        );

        $this->assertEquals(
            [
                'updatePost',
                'reader',
            ],
            array_keys($this->rolesStorage->getChildrenByName('author'))
        );
    }

    public function testRemoveChildren(): void
    {
        $author = $this->rolesStorage->getRoleByName('author');

        $this->manager->removeChildren($author);
        $this->assertFalse($this->rolesStorage->hasChildren('author'));
    }

    public function testHasChild(): void
    {
        $author = $this->rolesStorage->getRoleByName('author');
        $reader = $this->rolesStorage->getRoleByName('reader');
        $permission = $this->rolesStorage->getPermissionByName('createPost');

        $this->assertTrue($this->manager->hasChild($author, $permission));
        $this->assertFalse($this->manager->hasChild($reader, $permission));
    }

    public function testAssign(): void
    {
        $this->manager->assign(
            $this->rolesStorage->getRoleByName('reader'),
            'readingAuthor'
        );
        $this->manager->assign(
            $this->rolesStorage->getRoleByName('author'),
            'readingAuthor'
        );

        $this->assertEquals(
            [
                'myDefaultRole',
                'reader',
                'author',
            ],
            array_keys($this->manager->getRolesByUser('readingAuthor'))
        );
    }

    public function testAssignUnknownItem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown role "nonExistRole".');

        $this->manager->assign(
            new Role('nonExistRole'),
            'reader'
        );
    }

    public function testAssignAlreadyAssignedItem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"reader" role has already been assigned to user reader A.');

        $this->manager->assign(
            $this->rolesStorage->getRoleByName('reader'),
            'reader A'
        );
    }

    public function testGetRolesByUser(): void
    {
        $roleNames = array_keys($this->manager->getRolesByUser('reader A'));

        $this->assertSame(
            ['myDefaultRole', 'reader', 'observer'],
            $roleNames
        );
    }

    public function testGetChildRoles(): void
    {
        $this->assertEquals(
            ['admin', 'reader', 'author'],
            array_keys($this->manager->getChildRoles('admin'))
        );
    }

    public function testGetChildRolesUnknownRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Role "unknown" not found.');

        $this->manager->getChildRoles('unknown');
    }

    public function testGetPermissionsByRole(): void
    {
        $this->assertEquals(
            ['createPost', 'updatePost', 'readPost', 'updateAnyPost'],
            array_keys($this->manager->getPermissionsByRole('admin'))
        );

        $this->assertEmpty($this->manager->getPermissionsByRole('guest'));
    }

    public function testGetPermissionsByUser(): void
    {
        $this->assertEquals(
            ['createPost', 'updatePost', 'readPost'],
            array_keys($this->manager->getPermissionsByUser('author B'))
        );
    }

    public function testUserIdsByRole(): void
    {
        $this->assertEquals(
            [
                'reader A',
                'author B',
                'admin C',
            ],
            $this->manager->getUserIdsByRole('reader')
        );
        $this->assertEquals(
            [
                'author B',
                'admin C',
            ],
            $this->manager->getUserIdsByRole('author')
        );
        $this->assertEquals(['admin C'], $this->manager->getUserIdsByRole('admin'));
    }

    public function testAddRole(): void
    {
        $rule = new EasyRule();

        $role = (new Role('new role'))
            ->withDescription('new role description')
            ->withRuleName($rule->getName());

        $this->manager->addRole($role);
        $this->assertNotNull($this->rolesStorage->getRoleByName('new role'));
    }

    public function testRemoveRole(): void
    {
        $this->manager->removeRole($this->rolesStorage->getRoleByName('reader'));
        $this->assertNull($this->rolesStorage->getRoleByName('new role'));
    }

    public function testUpdateRoleNameAndRule(): void
    {
        $role = $this->rolesStorage->getRoleByName('reader')->withName('new reader');

        $this->assertNotNull($this->assignmentsStorage->getUserAssignmentByName('reader A', 'reader'));
        $this->assertNull($this->assignmentsStorage->getUserAssignmentByName('reader A', 'new reader'));

        $this->manager->updateRole('reader', $role);

        $this->assertNull($this->rolesStorage->getRoleByName('reader'));
        $this->assertNotNull($this->rolesStorage->getRoleByName('new reader'));

        $this->assertNull($this->assignmentsStorage->getUserAssignmentByName('reader A', 'reader'));
        $this->assertNotNull($this->assignmentsStorage->getUserAssignmentByName('reader A', 'new reader'));
    }

    public function testAddPermission(): void
    {
        $permission = (new Permission('edit post'))
            ->withDescription('edit a post');

        $this->manager->addPermission($permission);
        $this->assertNotNull($this->rolesStorage->getPermissionByName('edit post'));
    }

    public function testRemovePermission(): void
    {
        $this->manager->removePermission($this->rolesStorage->getPermissionByName('updatePost'));
        $this->assertNull($this->rolesStorage->getPermissionByName('updatePost'));
    }

    public function testUpdatePermission(): void
    {
        $permission = $this->rolesStorage->getPermissionByName('readPost')
            ->withName('newReadPost');

        $this->assertTrue($this->manager->userHasPermission('reader A', 'readPost'));
        $this->assertFalse($this->manager->userHasPermission('reader A', 'newReadPost'));

        $this->manager->updatePermission('readPost', $permission);

        $this->assertNull($this->rolesStorage->getPermissionByName('readPost'));
        $this->assertNotNull($this->rolesStorage->getPermissionByName('newReadPost'));

        $this->assertFalse($this->manager->userHasPermission('reader A', 'readPost'));
        $this->assertTrue($this->manager->userHasPermission('reader A', 'newReadPost'));
    }

    public function testUpdatePermissionNameAlreadyUsed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unable to change the role or the permission name. ' .
            'The name "createPost" is already used by another role or permission.'
        );

        $permission = $this->rolesStorage->getPermissionByName('updatePost')
            ->withName('createPost');

        $this->manager->updatePermission('updatePost', $permission);
    }

    public function testAddRule(): void
    {
        $ruleName = 'isReallyReallyAuthor';
        $rule = new AuthorRule($ruleName, true);

        $this->manager->addRule($rule);

        $rule = $this->rolesStorage->getRuleByName($ruleName);
        $this->assertEquals($ruleName, $rule->getName());
        $this->assertTrue($rule->isReallyReally());
    }

    public function testRemoveRule(): void
    {
        $this->manager->removeRule(
            $this->rolesStorage->getRuleByName('isAuthor')
        );

        $this->assertNull($this->rolesStorage->getRuleByName('isAuthor'));
    }

    public function testUpdateRule(): void
    {
        $rule = $this->rolesStorage->getRuleByName('isAuthor')
            ->withName('newName')
            ->withReallyReally(false);

        $this->manager->updateRule('isAuthor', $rule);
        $this->assertNull($this->rolesStorage->getRuleByName('isAuthor'));
        $this->assertNotNull($this->rolesStorage->getRuleByName('newName'));
    }

    public function testDefaultRolesSetWithClosure(): void
    {
        $this->manager->setDefaultRoleNames(
            static function () {
                return ['newDefaultRole'];
            }
        );

        $this->assertEquals(['newDefaultRole'], $this->manager->getDefaultRoleNames());
    }

    public function testDefaultRolesWithClosureReturningNonArrayValue(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Default role names closure must return an array');

        $this->manager->setDefaultRoleNames(
            static function () {
                return 'test';
            }
        );
    }

    public function testDefaultRolesWithNonArrayValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Default role names must be either an array or a closure');

        $this->manager->setDefaultRoleNames('test');
    }

    public function testGetDefaultRoles(): void
    {
        $this->assertEquals(['myDefaultRole'], $this->manager->getDefaultRoleNames());
    }

    protected function createManager(RolesStorageInterface $rolesStorage, AssignmentsStorageInterface $assignmentsStorage): Manager
    {
        return (new Manager($rolesStorage, $assignmentsStorage, new ClassNameRuleFactory()))
            ->setDefaultRoleNames(['myDefaultRole']);
    }

    private function createRolesStorage(string $datapath): RolesStorageInterface
    {
        $storage = new RolesStorage($datapath);

        $storage->addItem(new Permission('createPost'));
        $storage->addItem(new Permission('readPost'));
        $storage->addItem((new Permission('updatePost'))->withRuleName('isAuthor'));
        $storage->addItem(new Permission('updateAnyPost'));
        $storage->addItem(new Role('withoutChildren'));
        $storage->addItem(new Role('reader'));
        $storage->addItem(new Role('author'));
        $storage->addItem(new Role('admin'));
        $storage->addItem(new Role('observer'));

        $storage->addChild(new Role('reader'), new Permission('readPost'));
        $storage->addChild(new Role('author'), new Permission('createPost'));
        $storage->addChild(new Role('author'), new Permission('updatePost'));
        $storage->addChild(new Role('author'), new Role('reader'));
        $storage->addChild(new Role('admin'), new Role('author'));
        $storage->addChild(new Role('admin'), new Permission('updateAnyPost'));

        $storage->addRule(new AuthorRule('isAuthor'));

        return $storage;
    }

    private function createAssignmentsStorage(string $datapath): AssignmentsStorageInterface
    {
        $storage = new AssignmentsStorage($datapath);

        $storage->addAssignment('reader A', new Role('reader'));
        $storage->addAssignment('reader A', new Role('observer'));
        $storage->addAssignment('author B', new Role('author'));
        $storage->addAssignment('admin C', new Role('admin'));

        return $storage;
    }

    public function testRevokeRole(): void
    {
        $this->manager->revoke(
            $this->rolesStorage->getRoleByName('reader'),
            'reader A'
        );

        $this->assertEquals(['observer'], array_keys($this->assignmentsStorage->getUserAssignments('reader A')));
    }

    public function testRevokeAll(): void
    {
        $this->manager->revokeAll('author B');
        $this->assertEmpty($this->assignmentsStorage->getUserAssignments('author B'));
    }
}

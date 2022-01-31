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
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Php\Tests\AuthorRule;
use Yiisoft\Rbac\Php\Tests\EasyRule;
use Yiisoft\Rbac\Role;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\ClassNameRuleFactory;

/**
 * @group rbac
 */
class ManagerTest extends TestCase
{
    protected Manager $manager;

    protected ItemsStorageInterface $rolesStorage;

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

    /**
     * @dataProvider dataProviderUserHasPermission
     */
    public function testUserHasPermission($user, array $tests): void
    {
        $params = ['authorID' => 'author B'];

        foreach ($tests as $permission => $result) {
            $this->assertEquals(
                $result,
                $this->manager->userHasPermission($user, $permission, $params),
                "Checking \"$user\" can \"$permission\""
            );
        }
    }

    public function dataProviderUserHasPermission(): array
    {
        return [
            [
                'reader A',
                [
                    'createPost' => false,
                    'readPost' => true,
                    'updatePost' => false,
                    'updateAnyPost' => false,
                    'reader' => false,
                ],
            ],
            [
                'author B',
                [
                    'createPost' => true,
                    'readPost' => true,
                    'updatePost' => true,
                    'deletePost' => true,
                    'updateAnyPost' => false,
                ],
            ],
            [
                'admin C',
                [
                    'createPost' => true,
                    'readPost' => true,
                    'updatePost' => false,
                    'updateAnyPost' => true,
                    'nonExistingPermission' => false,
                    null => false,
                ],
            ],
            [
                'guest',
                [
                    'createPost' => false,
                    'readPost' => false,
                    'updatePost' => false,
                    'deletePost' => false,
                    'updateAnyPost' => false,
                    'blablabla' => false,
                    null => false,
                ],
            ],
            [
                12,
                [
                    'createPost' => false,
                    'readPost' => false,
                    'updatePost' => false,
                    'deletePost' => false,
                    'updateAnyPost' => false,
                    'blablabla' => false,
                    null => false,
                ],
            ],
        ];
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
            $this->rolesStorage->getRole('reader'),
            $this->rolesStorage->getPermission('createPost')
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
            $this->rolesStorage->getPermission('createPost')
        );
    }

    public function testAddChildEqualName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add "createPost" as a child of itself.');

        $this->manager->addChild(
            new Role('createPost'),
            $this->rolesStorage->getPermission('createPost')
        );
    }

    public function testAddChildPermissionToRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Can not add "reader" role as a child of "createPost" permission.');

        $this->manager->addChild(
            $this->rolesStorage->getPermission('createPost'),
            $this->rolesStorage->getRole('reader')
        );
    }

    public function testAddChildAlreadyAdded(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The item "reader" already has a child "readPost".');

        $this->manager->addChild(
            $this->rolesStorage->getRole('reader'),
            $this->rolesStorage->getPermission('readPost')
        );
    }

    public function testAddChildDetectLoop(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot add "author" as a child of "reader". A loop has been detected.');

        $this->manager->addChild(
            $this->rolesStorage->getRole('reader'),
            $this->rolesStorage->getRole('author'),
        );
    }

    public function testRemoveChild(): void
    {
        $this->manager->removeChild(
            $this->rolesStorage->getRole('author'),
            $this->rolesStorage->getPermission('createPost'),
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
        $this->manager->removeChildren('author');
        $this->assertFalse($this->rolesStorage->hasChildren('author'));
    }

    public function testHasChild(): void
    {
        $author = $this->rolesStorage->getRole('author');
        $reader = $this->rolesStorage->getRole('reader');
        $permission = $this->rolesStorage->getPermission('createPost');

        $this->assertTrue($this->manager->hasChild($author, $permission));
        $this->assertFalse($this->manager->hasChild($reader, $permission));
    }

    public function testAssign(): void
    {
        $this->manager->assign(
            $this->rolesStorage->getRole('reader'),
            'readingAuthor'
        );
        $this->manager->assign(
            $this->rolesStorage->getRole('author'),
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
            $this->rolesStorage->getRole('reader'),
            'reader A'
        );
    }

    public function testGetRolesByUser(): void
    {
        $this->assertEquals(
            ['myDefaultRole', 'reader'],
            array_keys($this->manager->getRolesByUser('reader A'))
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
            ['deletePost', 'createPost', 'updatePost', 'readPost'],
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
        $this->assertNotNull($this->rolesStorage->getRole('new role'));
    }

    public function testRemoveRole(): void
    {
        $this->manager->removeRole('reader');
        $this->assertNull($this->rolesStorage->getRole('new role'));
    }

    public function testUpdateRoleNameAndRule(): void
    {
        $role = $this->rolesStorage->getRole('reader')->withName('new reader');

        $this->assertNotNull($this->assignmentsStorage->get('reader A', 'reader'));
        $this->assertNull($this->assignmentsStorage->get('reader A', 'new reader'));

        $this->manager->updateRole('reader', $role);

        $this->assertNull($this->rolesStorage->getRole('reader'));
        $this->assertNotNull($this->rolesStorage->getRole('new reader'));

        $this->assertNull($this->assignmentsStorage->get('reader A', 'reader'));
        $this->assertNotNull($this->assignmentsStorage->get('reader A', 'new reader'));
    }

    public function testAddPermission(): void
    {
        $permission = (new Permission('edit post'))
            ->withDescription('edit a post');

        $this->manager->addPermission($permission);
        $this->assertNotNull($this->rolesStorage->getPermission('edit post'));
    }

    public function testRemovePermission(): void
    {
        $this->manager->removePermission('updatePost');
        $this->assertNull($this->rolesStorage->getPermission('updatePost'));
    }

    public function testUpdatePermission(): void
    {
        $permission = $this->rolesStorage->getPermission('deletePost')
            ->withName('newDeletePost');

        $this->assertNotNull($this->assignmentsStorage->get('author B', 'deletePost'));
        $this->assertNull($this->assignmentsStorage->get('author B', 'newDeletePost'));

        $this->manager->updatePermission('deletePost', $permission);

        $this->assertNull($this->rolesStorage->getPermission('deletePost'));
        $this->assertNotNull($this->rolesStorage->getPermission('newDeletePost'));

        $this->assertNull($this->assignmentsStorage->get('author B', 'deletePost'));
        $this->assertNotNull($this->assignmentsStorage->get('author B', 'newDeletePost'));
    }

    public function testUpdatePermissionNameAlreadyUsed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unable to change the role or the permission name. ' .
            'The name "createPost" is already used by another role or permission.'
        );

        $permission = $this->rolesStorage->getPermission('updatePost')
            ->withName('createPost');

        $this->manager->updatePermission('updatePost', $permission);
    }

    public function testAddRule(): void
    {
        $ruleName = 'isReallyReallyAuthor';
        $rule = new AuthorRule($ruleName, true);

        $this->manager->addRule($rule);

        $rule = $this->rolesStorage->getRule($ruleName);
        $this->assertEquals($ruleName, $rule->getName());
        $this->assertTrue($rule->isReallyReally());
    }

    public function testRemoveRule(): void
    {
        $this->manager->removeRule(
            $this->rolesStorage->getRule('isAuthor')
        );

        $this->assertNull($this->rolesStorage->getRule('isAuthor'));
    }

    public function testUpdateRule(): void
    {
        $rule = $this->rolesStorage->getRule('isAuthor')
            ->withName('newName')
            ->withReallyReally(false);

        $this->manager->updateRule('isAuthor', $rule);
        $this->assertNull($this->rolesStorage->getRule('isAuthor'));
        $this->assertNotNull($this->rolesStorage->getRule('newName'));
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

    protected function createManager(ItemsStorageInterface $rolesStorage, AssignmentsStorageInterface $assignmentsStorage): Manager
    {
        return (new Manager($rolesStorage, $assignmentsStorage, new ClassNameRuleFactory()))
            ->setDefaultRoleNames(['myDefaultRole']);
    }

    protected function createRolesStorage(string $datapath): ItemsStorageInterface
    {
        $storage = new ItemsStorage($datapath);

        $storage->add(new Permission('Fast Metabolism'));
        $storage->add(new Permission('createPost'));
        $storage->add(new Permission('readPost'));
        $storage->add(new Permission('deletePost'));
        $storage->add((new Permission('updatePost'))->withRuleName('isAuthor'));
        $storage->add(new Permission('updateAnyPost'));
        $storage->add(new Role('withoutChildren'));
        $storage->add(new Role('reader'));
        $storage->add(new Role('author'));
        $storage->add(new Role('admin'));

        $storage->addChild('reader', 'readPost');
        $storage->addChild('author', 'createPost');
        $storage->addChild('author', 'updatePost');
        $storage->addChild('author', 'reader');
        $storage->addChild('admin', 'author');
        $storage->addChild('admin', 'updateAnyPost');

        $storage->addRule(new AuthorRule('isAuthor'));

        return $storage;
    }

    protected function createAssignmentsStorage(string $datapath): AssignmentsStorageInterface
    {
        $storage = new AssignmentsStorage($datapath);

        $storage->add('reader A', 'Fast Metabolism');
        $storage->add('reader A', 'reader');
        $storage->add('author B', 'author');
        $storage->add('author B', 'deletePost');
        $storage->add('admin C', 'admin');

        return $storage;
    }

    public function testRevokeRole(): void
    {
        $this->manager->revoke(
            $this->rolesStorage->getRole('reader'),
            'reader A'
        );

        $this->assertEquals(['Fast Metabolism'], array_keys($this->assignmentsStorage->getUserAssignments('reader A')));
    }

    public function testRevokePermission(): void
    {
        $this->manager->revoke(
            $this->rolesStorage->getPermission('deletePost'),
            'author B'
        );

        $this->assertEquals(['author'], array_keys($this->assignmentsStorage->getUserAssignments('author B')));
    }

    public function testRevokeAll(): void
    {
        $this->manager->revokeAll('author B');
        $this->assertEmpty($this->assignmentsStorage->getUserAssignments('author B'));
    }
}

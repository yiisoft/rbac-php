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
use Yiisoft\Rbac\Php\Tests\Support\SimpleRulesFactory;
use Yiisoft\Rbac\Role;
use Yiisoft\Rbac\ItemsStorageInterface;

/**
 * @group rbac
 */
class ManagerTest extends TestCase
{
    protected Manager $manager;

    protected ItemsStorageInterface $itemsStorage;

    protected AssignmentsStorageInterface $assignmentsStorage;

    private function getDataPath(): string
    {
        return sys_get_temp_dir() . '/' . str_replace('\\', '_', static::class) . uniqid('', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $dataPath = $this->getDataPath();

        $this->itemsStorage = $this->createItemsStorage($dataPath);
        $this->assignmentsStorage = $this->createAssignmentsStorage($dataPath);

        $this->manager = $this->createManager($this->itemsStorage, $this->assignmentsStorage);
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
                'author',
                'reader'
            )
        );
    }

    public function testCanAddChildDetectLoop(): void
    {
        $this->assertFalse(
            $this->manager->canAddChild(
                'reader',
                'author'
            )
        );
    }

    public function testAddChild(): void
    {
        $this->manager->addChild(
            'reader',
            'createPost'
        );

        $this->assertEquals(
            [
                'readPost',
                'createPost',
            ],
            array_keys($this->itemsStorage->getChildren('reader'))
        );
    }

    public function testAddChildNotHasItem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Either "new reader" does not exist.');

        $this->manager->addChild(
            'new reader',
            'createPost'
        );
    }

    public function testAddChildEqualName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add "createPost" as a child of itself.');

        $this->manager->addChild(
            'createPost',
            'createPost'
        );
    }

    public function testAddChildPermissionToRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Can not add "reader" role as a child of "createPost" permission.');

        $this->manager->addChild(
            'createPost',
            'reader'
        );
    }

    public function testAddChildAlreadyAdded(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The item "reader" already has a child "readPost".');

        $this->manager->addChild(
            'reader',
            'readPost'
        );
    }

    public function testAddChildDetectLoop(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot add "author" as a child of "reader". A loop has been detected.');

        $this->manager->addChild(
            'reader',
            'author',
        );
    }

    public function testRemoveChild(): void
    {
        $this->manager->removeChild(
            'author',
            'createPost',
        );

        $this->assertEquals(
            [
                'updatePost',
                'reader',
            ],
            array_keys($this->itemsStorage->getChildren('author'))
        );
    }

    public function testRemoveChildren(): void
    {
        $this->manager->removeChildren('author');
        $this->assertFalse($this->itemsStorage->hasChildren('author'));
    }

    public function testHasChild(): void
    {
        $this->assertTrue($this->manager->hasChild('author', 'createPost'));
        $this->assertFalse($this->manager->hasChild('reader', 'createPost'));
    }

    public function testAssign(): void
    {
        $this->manager->assign(
            'reader',
            'readingAuthor'
        );
        $this->manager->assign(
            'author',
            'readingAuthor'
        );

        $this->assertEquals(
            [
                'myDefaultRole',
                'reader',
                'author',
            ],
            array_keys($this->manager->getRolesByUserId('readingAuthor'))
        );
    }

    public function testAssignUnknownItem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('There is no item named "nonExistRole".');

        $this->manager->assign(
            'nonExistRole',
            'reader'
        );
    }

    public function testAssignAlreadyAssignedItem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"reader" role has already been assigned to user reader A.');

        $this->manager->assign(
            'reader',
            'reader A'
        );
    }

    public function testGetRolesByUser(): void
    {
        $this->assertEquals(
            ['myDefaultRole', 'reader'],
            array_keys($this->manager->getRolesByUserId('reader A'))
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
            array_keys($this->manager->getPermissionsByRoleName('admin'))
        );

        $this->assertEmpty($this->manager->getPermissionsByRoleName('guest'));
    }

    public function testGetPermissionsByUser(): void
    {
        $this->assertEquals(
            ['deletePost', 'createPost', 'updatePost', 'readPost'],
            array_keys($this->manager->getPermissionsByUserId('author B'))
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
            $this->manager->getUserIdsByRoleName('reader')
        );
        $this->assertEquals(
            [
                'author B',
                'admin C',
            ],
            $this->manager->getUserIdsByRoleName('author')
        );
        $this->assertEquals(['admin C'], $this->manager->getUserIdsByRoleName('admin'));
    }

    public function testAddRole(): void
    {
        $role = (new Role('new role'))
            ->withDescription('new role description')
            ->withRuleName(EasyRule::class);

        $this->manager->addRole($role);
        $this->assertNotNull($this->itemsStorage->getRole('new role'));
    }

    public function testRemoveRole(): void
    {
        $this->manager->removeRole('reader');
        $this->assertNull($this->itemsStorage->getRole('new role'));
    }

    public function testUpdateRoleNameAndRule(): void
    {
        $role = $this->itemsStorage->getRole('reader')->withName('new reader');

        $this->assertNotNull($this->assignmentsStorage->get('reader', 'reader A'));
        $this->assertNull($this->assignmentsStorage->get('new reader', 'reader A'));

        $this->manager->updateRole('reader', $role);

        $this->assertNull($this->itemsStorage->getRole('reader'));
        $this->assertNotNull($this->itemsStorage->getRole('new reader'));

        $this->assertNull($this->assignmentsStorage->get('reader', 'reader A'));
        $this->assertNotNull($this->assignmentsStorage->get('new reader', 'reader A'));
    }

    public function testAddPermission(): void
    {
        $permission = (new Permission('edit post'))
            ->withDescription('edit a post');

        $this->manager->addPermission($permission);
        $this->assertNotNull($this->itemsStorage->getPermission('edit post'));
    }

    public function testRemovePermission(): void
    {
        $this->manager->removePermission('updatePost');
        $this->assertNull($this->itemsStorage->getPermission('updatePost'));
    }

    public function testUpdatePermission(): void
    {
        $permission = $this->itemsStorage->getPermission('deletePost')
            ->withName('newDeletePost');

        $this->assertNotNull($this->assignmentsStorage->get('deletePost', 'author B'));
        $this->assertNull($this->assignmentsStorage->get('newDeletePost', 'author B'));

        $this->manager->updatePermission('deletePost', $permission);

        $this->assertNull($this->itemsStorage->getPermission('deletePost'));
        $this->assertNotNull($this->itemsStorage->getPermission('newDeletePost'));

        $this->assertNull($this->assignmentsStorage->get('deletePost', 'author B'));
        $this->assertNotNull($this->assignmentsStorage->get('newDeletePost', 'author B'));
    }

    public function testUpdatePermissionNameAlreadyUsed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unable to change the role or the permission name. ' .
            'The name "createPost" is already used by another role or permission.'
        );

        $permission = $this->itemsStorage->getPermission('updatePost')
            ->withName('createPost');

        $this->manager->updatePermission('updatePost', $permission);
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
        $rulesFactory = new SimpleRulesFactory([
            'isAuthor' => new AuthorRule(),
        ]);
        return (new Manager($rolesStorage, $assignmentsStorage, $rulesFactory))
            ->setDefaultRoleNames(['myDefaultRole']);
    }

    protected function createItemsStorage(string $dataPath): ItemsStorageInterface
    {
        $storage = new ItemsStorage($dataPath);

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
        $storage->add(new Role('myDefaultRole'));

        $storage->addChild('reader', 'readPost');
        $storage->addChild('author', 'createPost');
        $storage->addChild('author', 'updatePost');
        $storage->addChild('author', 'reader');
        $storage->addChild('admin', 'author');
        $storage->addChild('admin', 'updateAnyPost');

        return $storage;
    }

    protected function createAssignmentsStorage(string $dataPath): AssignmentsStorageInterface
    {
        $storage = new AssignmentsStorage($dataPath);

        $storage->add('Fast Metabolism', 'reader A');
        $storage->add('reader', 'reader A');
        $storage->add('author', 'author B');
        $storage->add('deletePost', 'author B');
        $storage->add('admin', 'admin C');

        return $storage;
    }

    public function testRevokeRole(): void
    {
        $this->manager->revoke(
            'reader',
            'reader A'
        );

        $this->assertEquals(['Fast Metabolism'], array_keys($this->assignmentsStorage->getByUserId('reader A')));
    }

    public function testRevokePermission(): void
    {
        $this->manager->revoke(
            'deletePost',
            'author B'
        );

        $this->assertEquals(['author'], array_keys($this->assignmentsStorage->getByUserId('author B')));
    }

    public function testRevokeAll(): void
    {
        $this->manager->revokeAll('author B');
        $this->assertEmpty($this->assignmentsStorage->getByUserId('author B'));
    }
}

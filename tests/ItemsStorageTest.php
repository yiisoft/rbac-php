<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Php\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Files\FileHelper;
use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Role;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\Tests\Common\ItemsStorageTestTrait;

final class ItemsStorageTest extends TestCase
{
    use FixtureTrait;
    use ItemsStorageTestTrait;

    protected function setUp(): void
    {
        if ($this->getName() === 'testFailCreateDirectory' || $this->getName() === 'testCreateNestedDirectory') {
            FileHelper::ensureDirectory($this->getTempDirectory());
            FileHelper::clearDirectory($this->getTempDirectory());
        }

        $this->populateStorage();
    }

    protected function tearDown(): void
    {
        if ($this->getName() === 'testFailCreateDirectory' || $this->getName() === 'testCreateNestedDirectory') {
            FileHelper::removeDirectory($this->getTempDirectory());
        }

        $this->clearFixturesFiles();
    }

    public function testFailCreateDirectory(): void
    {
        $directory = $this->getTempDirectory() . '/file.txt';
        touch($directory);

        $storage = new ItemsStorage($directory);
        $permission = new Permission('createPost');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create directory "' . $directory . '". mkdir(): File exists');
        $storage->add($permission);
    }

    public function testCreateNestedDirectory(): void
    {
        $directory = $this->getTempDirectory() . '/test/create/nested/directory';

        $storage = new ItemsStorage($directory);
        $storage->add(new Permission('createPost'));

        $this->assertFileExists($directory . '/items.php');
    }

    private function populateStorage(): void
    {
        $storage = $this->getStorage();
        $fixtures = $this->getFixtures();
        foreach ($fixtures['items'] as $itemData) {
            $name = $itemData['name'];
            $item = $itemData['type'] === Item::TYPE_PERMISSION ? new Permission($name) : new Role($name);
            $item = $item
                ->withCreatedAt($itemData['createdAt'])
                ->withUpdatedAt($itemData['updatedAt']);
            $storage->add($item);
        }

        foreach ($fixtures['itemsChildren'] as $itemChildData) {
            $storage->addChild($itemChildData['parent'], $itemChildData['child']);
        }
    }

    private function getStorage(): ItemsStorageInterface
    {
        return new ItemsStorage($this->getDataPath());
    }

    private function getTempDirectory(): string
    {
        return __DIR__ . '/temp';
    }
}

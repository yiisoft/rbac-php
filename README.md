<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii RBAC PHP File Storage</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/rbac-php/v)](https://packagist.org/packages/yiisoft/rbac-php)
[![Total Downloads](https://poser.pugx.org/yiisoft/rbac-php/downloads)](https://packagist.org/packages/yiisoft/rbac-php)
[![Build status](https://github.com/yiisoft/rbac-php/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/rbac-php/actions/workflows/build.yml)
[![codecov](https://codecov.io/gh/yiisoft/rbac-php/graph/badge.svg?token=YU8LVBNCQ8)](https://codecov.io/gh/yiisoft/rbac-php)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Frbac-php%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/rbac-php/master)
[![static analysis](https://github.com/yiisoft/rbac-php/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/rbac-php/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/rbac-php/coverage.svg)](https://shepherd.dev/github/yiisoft/rbac-php)

This package provides PHP file-based storage for [RBAC (Role-Based Access Control)](https://github.com/yiisoft/rbac)
package.

## Requirements

- PHP 8.1 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/rbac-php
```

See [yiisoft/rbac](https://github.com/yiisoft/rbac) for RBAC package installation instructions.

## General usage

The storage is suitable for authorization data that is not too big (for example, the authorization data for a personal 
blog system) or for fairly static RBAC hierarchy.

Authorization data is stored in PHP files. PHP should be able to read and write these files. Non-existing files will be 
created automatically on any write operation.

### Using storages

The storages are not intended to be used directly. Instead, use them with `Manager` from
[Yii RBAC](https://github.com/yiisoft/rbac) package:

```php
use Yiisoft\Rbac\Manager;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Php\AssignmentsStorage;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\RuleFactoryInterface;

$directory = __DIR__ . '/rbac';
$itemsStorage = new ItemsStorage($directory . '/items.php');
$assignmentsStorage = new AssignmentsStorage($directory . '/assignments.php');
/** @var RuleFactoryInterface $rulesContainer */
$manager = new Manager(
    itemsStorage: $itemsStorage, 
    assignmentsStorage: $assignmentsStorage,
    // Requires https://github.com/yiisoft/rbac-rules-container or other compatible factory.
    ruleFactory: $rulesContainer,
),
$manager->addPermission(new Permission('posts.create'));
```

> Note that it's not necessary to use both PHP storages. Combining different implementations is possible. A quite
> popular case is to manage items via PHP file while store assignments in database (see 
> [Cycle](https://github.com/yiisoft/rbac-cycle-db) and [Yiisoft DB](https://github.com/yiisoft/rbac-db) 
> implementations).

More examples can be found in [Yii RBAC](https://github.com/yiisoft/rbac) documentation.

### File structure

In case you decide to manually edit the files, make sure to keep the following structure.

#### Items

Required and optional fields:

```php
return [
    [
        'name' => 'posts.update',
        'description' => 'Update a post', // Optional
        'rule_name' => 'is_author', // Optional
        'type' => 'permission', // or 'role'        
        'created_at' => 1683707079, // UNIX timestamp, optional
        'updated_at' => 1683707079, // UNIX timestamp, optional
    ],
];
```

While it's recommended to maintain created and updated timestamps, if any is missing, the file modification time will 
be used instead as a fallback.

The structure for an item with children:

```php
return [
    [
        'name' => 'posts.redactor',
        'type' => 'role',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
        'children' => [
            'posts.viewer',
            'posts.create',
            'posts.update',
        ],
    ],
];
```

The complete example for managing posts:

```php
return [
    [
        'name' => 'posts.admin',        
        'type' => 'role',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
        'children' => [
            'posts.redactor',
            'posts.delete',
            'posts.update.all',
        ],
    ],
    [
        'name' => 'posts.redactor',
        'type' => 'role',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
        'children' => [
            'posts.viewer',
            'posts.create',
            'posts.update',
        ],
    ],
    [
        'name' => 'posts.viewer',
        'type' => 'role',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
        'children' => [
            'posts.view',
        ],
    ],
    [
        'name' => 'posts.view',
        'type' => 'permission',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
    ],
    [
        'name' => 'posts.create',
        'type' => 'permission',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
    ],
    [
        'name' => 'posts.update',
        'rule_name' => 'is_author',
        'type' => 'permission',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
    ],
    [
        'name' => 'posts.delete',        
        'type' => 'permission',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
    ],
    [
        'name' => 'posts.update.all',
        'type' => 'permission',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
    ],
];
```

#### Assignments

```php
return [
    [
        'item_name' => 'posts.redactor',
        'user_id' => 'john',
        'created_at' => 1683707079, // Optional
    ],
    // ...
    [
        'item_name' => 'posts.admin',
        'user_id' => 'jack',
        'created_at' => 1683707079,
    ],
];
```

While it's recommended to maintain created timestamps, if it is missing, the file modification time will be used 
instead as a fallback.

### Concurrency

By default, working with PHP storage does not support concurrency. This might be OK if you store its files under VCS for
example. If your scenario is different and, let's say, some kind of web interface is used - then, to enable concurrency, 
do not use the storage directly - wrap it with decorator instead:

```php
use Yiisoft\Rbac\Manager;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Php\AssignmentsStorage;
use Yiisoft\Rbac\Php\ConcurrentAssignmentsStorageDecorator;
use Yiisoft\Rbac\Php\ConcurrentItemsStorageDecorator;
use Yiisoft\Rbac\Php\ItemsStorage;
use Yiisoft\Rbac\RuleFactoryInterface;

$directory = __DIR__ . DIRECTORY_SEPARATOR . 'rbac';
$itemsSstorage = new ConcurrentItemsStorageDecorator(ItemsStorage($directory));
$assignmentsStorage = new ConcurrentAssignmentsStorageDecorator(AssignmentsStorage($directory));
/** @var RuleFactoryInterface $rulesContainer */
$manager = new Manager(
    itemsStorage: $itemsStorage, 
    assignmentsStorage: $assignmentsStorage,
    // Requires https://github.com/yiisoft/rbac-rules-container or other compatible factory.
    ruleFactory: $rulesContainer,
),
```

> Note that it will have an impact on performance so don't use it unless you really have to.

#### Configuring file updated time

A closure can be used to customize getting file modification time:

```php
use Yiisoft\Rbac\Php\AssignmentsStorage;
use Yiisoft\Rbac\Php\ItemsStorage;

$directory = __DIR__ . '/rbac',
$getFileUpdatedAt = static fn (string $filePath): int|false => @filemtime($filePath)
$itemsStorage = new ItemsStorage(
    $directory . '/items.php',
    getFileUpdatedAt: static fn (string $filePath): int|false => @filemtime($filePath),
);
$itemsStorage = new AssignmentsStorage(
    $directory . '/assignments.php',
    getFileUpdatedAt: static fn (string $filePath): int|false => @filemtime($filePath),
);
```

This is useful for 2 things:

- Using for empty timestamps when files are edited manually.
- Detection of file changes when concurrency is enabled. This helps to optimize perfomance by preventing of unnecessary
loads (when file contents has not been changed).

### Syncing storages manually

The storages stay synced thanks to manager, but there can be situations where you need to sync them manually. One of
them is [editing storage manually](https://github.com/yiisoft/rbac-php/?tab=readme-ov-file#file-structure).

Let's say PHP files are used for both items and assignments and some items were deleted.

```diff
return [
    [
        'name' => 'posts.admin',        
        'type' => 'role',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
        'children' => [
            'posts.redactor',
            'posts.delete',
            'posts.update.all',
        ],
    ],
-   [
-       'name' => 'posts.redactor',
-       'type' => 'role',        
-       'created_at' => 1683707079,
-       'updated_at' => 1683707079,
-       'children' => [
-           'posts.viewer',
-           'posts.create',
-           'posts.update',
-       ],
-   ],
    [
        'name' => 'posts.viewer',
        'type' => 'role',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
        'children' => [
            'posts.view',
        ],
    ],
    [
        'name' => 'posts.view',
        'type' => 'permission',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
    ],
    [
        'name' => 'posts.create',
        'type' => 'permission',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
    ],
-   [
-       'name' => 'posts.update',
-       'rule_name' => 'is_author',
-       'type' => 'permission',
-       'created_at' => 1683707079,
-       'updated_at' => 1683707079,
-   ],
    [
        'name' => 'posts.delete',        
        'type' => 'permission',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
    ],
    [
        'name' => 'posts.update.all',
        'type' => 'permission',        
        'created_at' => 1683707079,
        'updated_at' => 1683707079,
    ],
];
```

Then related entries in assignments storage needs to be deleted as well:

```diff
return [
-   [
-       'item_name' => 'posts.redactor',
-       'user_id' => 'john',
-       'created_at' => 1683707079,
-   ],
    [
        'item_name' => 'posts.admin',
        'user_id' => 'jack',
        'created_at' => 1683707079,
    ],
];
```

When using database as a second storage, this can be done within a migration. Depending on chosen implementation, refer
to either [RBAC Cycle example](https://github.com/yiisoft/rbac-cycle-db?tab=readme-ov-file#syncing-storages-manually) or 
[RBAC DB example](https://github.com/yiisoft/rbac-db?tab=readme-ov-file#syncing-storages-manually).

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii RBAC PHP File Storage is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)

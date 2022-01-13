<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii RBAC PHP file storage</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/rbac-php/v/stable.png)](https://packagist.org/packages/yiisoft/rbac-php)
[![Total Downloads](https://poser.pugx.org/yiisoft/rbac-php/downloads.png)](https://packagist.org/packages/yiisoft/rbac-php)
[![Build status](https://github.com/yiisoft/rbac-php/workflows/build/badge.svg)](https://github.com/yiisoft/rbac-php/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/rbac-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/rbac-php/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/rbac-php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/rbac-php/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Frbac-php%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/rbac-php/master)
[![static analysis](https://github.com/yiisoft/rbac-php/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/rbac-php/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/rbac-php/coverage.svg)](https://shepherd.dev/github/yiisoft/rbac-php)

This package provides PHP file-based storage for [RBAC (Role-Based Access Control)](https://github.com/yiisoft/rbac) package. 

[RBAC]: https://en.wikipedia.org/wiki/Role-based_access_control
[Yii Framework]: https://yiiframework.com

## Requirements

- PHP 7.4 or higher.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/rbac-php --prefer-dist
```

See [yiisoft/rbac](https://github.com/yiisoft/rbac) for RBAC package installation instructions.

## General usage

The storage is suitable for authorization data that is not too big (for example, the authorization data for
a personal blog system) or for fairly static RBAC hierarchy.

Authorization data is stored in three PHP files specified by `Storage::$itemFile`, `Storage::$assignmentFile`,
and `Storage::$ruleFile`.

PHP should be able to read and write these files. Non-existing files will be created automatically on any write operation.

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework with
[Infection Static Analysis Plugin](https://github.com/Roave/infection-static-analysis-plugin). To run it:

```shell
./vendor/bin/roave-infection-static-analysis-plugin
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## License

The Yii Dependency Injection is free software. It is released under the terms of the BSD License.
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

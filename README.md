<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii RBAC PHP file storage</h1>
    <br>
</p>

[RBAC]: https://en.wikipedia.org/wiki/Role-based_access_control
[Yii Framework]: https://yiiframework.com

This package provides storage for [RBAC (Role-Based Access Control)](https://github.com/yiisoft/rbac) package. 
It is used in Yii Framework but is usable separately.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://poser.pugx.org/yiisoft/rbac-php/v/stable.png)](https://packagist.org/packages/yiisoft/rbac-php)
[![Total Downloads](https://poser.pugx.org/yiisoft/rbac-php/downloads.png)](https://packagist.org/packages/yiisoft/rbac-php)
[![Build status](https://github.com/yiisoft/rbac-php/workflows/build/badge.svg)](https://github.com/yiisoft/rbac-php/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/rbac-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/rbac-php/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/rbac-php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/rbac-php/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Frbac-php%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/rbac-php/master)
[![static analysis with phan](https://github.com/yiisoft/rbac-php/workflows/static%20analysis%20with%20phan/badge.svg)](https://github.com/yiisoft/rbac-php/actions?query=workflow%3A%22static+analysis+with+phan%22)

## Install:

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

```
composer require yiisoft/rbac-php
```

## General usage

Storage stores authorization data in three PHP files specified by 
`Storage::$itemFile`, `Storage::$assignmentFile`, `Storage::$ruleFile`

PHP should be able to read and write these files. Non-existing files will be created automatically on any save operation.

It is suitable for authorization data that is not too big (for example, the authorization data for
 a personal blog system).



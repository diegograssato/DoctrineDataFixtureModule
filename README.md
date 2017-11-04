# DoctrineDataFixture Module for Zend Framework 3
 
## Introduction

The DoctrineDataFixtureModule module intends to integrate Doctrine 2 data-fixture with Zend Framework 3 quickly
and easily. The following features are intended to work out of the box:

  - Doctrine ORM support
  - Multiple ORM entity managers
  - Multiple DBAL connections
  - Support reuse existing PDO connections in DBAL

## Requirements

This module is designed to work with a typical [ZF2 MVC application](https://github.com/zendframework/ZendSkeletonApplication).

## Installation

Installation of this module uses composer. For composer documentation, please refer to
[getcomposer.org](http://getcomposer.org/).

```sh
$ php composer.phar require --dev "diegograssato/doctrine-odm-datafixture": "2.0"
```

Then open `config/development.config.php` and `DoctrineDataFixtureModule` to your `modules`

#### Registering Fixtures

To register fixtures with Doctrine module add the fixtures in your configuration.

```php

 'orm_fixtures' => [
     __DIR__.'/../MyModule/src/MyModule/Fixtures',
  ]
  
```


or group configurator

```php
'orm_fixtures' => [
    'groups' => [
        'default' => [
            __DIR__.'/../MyModule/src/MyModule/Fixtures/default',
        ],
        'production' => [
             __DIR__.'/../MyModule/src/MyModule/Fixtures/production',
        ]
    ]
]
```

To rotate the fixture use the terminal command:

```
  vendor/bin/doctrine-odm-datafixture odm:fixtures:load
```

The odm:fixture:load command loads data fixtures from your bundles:

```
  vendor/bin/doctrine-module orm:fixtures:load
```

You can also optionally specify the path to fixtures with the **--fixtures** option:

```
  vendor/bin/doctrine-module orm:fixtures:load --fixture=/path/to/fixtures1 --fixture=/path/to/fixtures2
```
If you want to append the fixtures instead of flushing the database first you can use the **--append** option:

```
  vendor/bin/doctrine-module orm:fixtures:load --fixture=/path/to/fixtures1 --fixture=/path/to/fixtures2 --append
```

You can also optionally specify the group configuration:

```
  vendor/bin/doctrine-module orm:fixtures:load --group production
``` 

You can also optionally list the fixtures:
```
  vendor/bin/doctrine-module orm:fixtures:list --group production
```
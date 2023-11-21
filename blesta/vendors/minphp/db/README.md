# minphp/Db

[![Build Status](https://travis-ci.org/phillipsdata/minphp-db.svg?banch=master)](https://travis-ci.org/phillipsdata/minphp-db) [![Coverage Status](https://coveralls.io/repos/phillipsdata/minphp-db/badge.svg)](https://coveralls.io/r/phillipsdata/minphp-db)

Database Connection Library.

Efficiently manages a connection, preventing a new one from being established if a matching connection already exists.

## Installation

Install via composer:

```sh
composer require minphp/db:dev-master
```

## Basic Usage

```php
use Minphp\Db\PdoConnection;

$dbInfo = array(
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'databasename',
    'user' => 'user',
    'pass' => 'pass'
);

$connection = new PdoConnection($dbInfo);
$connection->query('SELECT * FROM table WHERE id=?', 1);
```

### Explicitly Connecting

By default, PdoConnection will only connect to the database when a connection is required. To explicitly connect to the database use `connect()`:

```php
use Minphp\Db\PdoConnection;

$dbInfo = array(
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'databasename',
    'user' => 'user',
    'pass' => 'pass'
);

$connection = new PdoConnection($dbInfo);
$connection->connect();
// Connection now ready and waiting
```

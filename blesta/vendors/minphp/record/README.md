# Minphp/Record

[![Build Status](https://travis-ci.org/phillipsdata/minphp-record.svg?banch=master)](https://travis-ci.org/phillipsdata/minphp-record) [![Coverage Status](https://coveralls.io/repos/phillipsdata/minphp-record/badge.svg)](https://coveralls.io/r/phillipsdata/minphp-record)

Database Access Library.

Provides a fluent interface for generating and executing SQL queries.

## Installation

Install via composer:

```sh
composer require minphp/record:~1.0
```

## Usage

First, initialize your connection:

```php
use Minphp\Record\Record;

$dbInfo = array(
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'databasename',
    'user' => 'user',
    'pass' => 'pass'
);

$record = new Record($dbInfo);
```

### Select

Select statements must end with one of the following:

- `fetch()` fetch a single record
- `fetchAll()` fetch all records
- `getStatement()` fetch the `\PDOStatement` object which you can iterate over
- `get()` fetch the SQL query

#### All

```php
$users = $record->select()
    ->from('users')
    ->fetchAll();
```

#### Tuples

```php
$users = $record->select(array('id', 'name', 'email'))
    ->from('users')
    ->fetchAll();
```

#### Tuple Aliasing

```php
$users = $record->select(array('id', 'name', 'email' => 'login'))
    ->from('users')
    ->fetchAll();
```

#### Value Injection

```php
$users = $record->select(array('id', 'name', 'email' => 'login'))
    ->select(array('\'active\'' => 'status'), false)
    ->from('users')
    ->fetchAll();
```

#### Aggregate Functions

```php
$users = $record->select(array('MAX(id)' => 'largestId'))
    ->from('users')
    ->fetch();
```

#### Number of Results

```php
$count = $record->select()
    ->from('users')
    ->numResults();
```

#### Number of Rows Affected

```php
$count = $record->affectedRows();
```

#### Last Insert ID

```php
$id = $record->lastInsertId();
```

### Limiting

Limit 10 records:

```php
$users = $record->select()
    ->from('users')
    ->limit(10)
    ->fetchAll();
```

Limit 10 records, starting at record 20:

```php
$users = $record->select()
    ->from('users')
    ->limit(10, 20)
    ->fetchAll();
```

### Ordering

```php
$users = $record->select()
    ->from('users')
    ->order(array('id' => 'asc'))
    ->fetchAll();
```


### Grouping

```php
$users = $record->select(array('email'))
    ->from('users')
    ->group(array('email'))
    ->fetchAll();
```

### Where

Operators include:

- `=` equality
- `!=`, `<>` inequality
- `>` greather than
- `>=` greather than or equal
- `<` less than
- `<=` less than or equal
- `in` in the given values
- `notin` not in the given values
- `exists` exists in the result set
- `notexists` does not exist in the result set

**Note:** If `null` is supplied as the value, with `=` or `!=` the result becomes `IS NULL` or `IS NOT NULL`, respectively.

#### Simple Where

```php
$users = $record->select()
    ->from('users')
    ->where('id', '=', 10)
    ->fetchAll();
```

#### And Where

```php
$users = $record->select()
    ->from('users')
    ->where('id', '=', 10)
    ->where('name', '=', 'Roger Sherman')
    ->fetchAll();
```

#### Or Where

```php
$users = $record->select()
    ->from('users')
    ->where('id', '=', 10)
    ->orWhere('name', '=', 'Roger Sherman')
    ->fetchAll();
```

#### Where In

```php
$users = $record->select()
    ->from('users')
    ->where('id', 'in', array(1, 2, 3, 4))
    ->fetchAll();
```

#### Simple Like

```php
$users = $record->select()
    ->from('users')
    ->like('name', 'Roger%')
    ->fetchAll();
```

#### And Like

```php
$users = $record->select()
    ->from('users')
    ->like('name', 'Roger%')
    ->like('email', '@domain.com')
    ->fetchAll();
```

#### Or Like

```php
$users = $record->select()
    ->from('users')
    ->like('name', 'Roger%')
    ->orLike('email', '@domain.com')
    ->fetchAll();
```

#### Simple Having

```php
$users = $record->select()
    ->from('users')
    ->having('name', '!=', null)
    ->fetchAll();
```

#### And Having

```php
$users = $record->select()
    ->from('users')
    ->having('name', '!=', null)
    ->having('email', '!=', null)
    ->fetchAll();
```

#### Or Having

```php
$users = $record->select()
    ->from('users')
    ->having('name', '!=', null)
    ->orHaving('email', '!=', null)
    ->fetchAll();
```

### Conditional Grouping

```php
$users = $record->select()
    ->from('users')
    ->open()
        ->where('id', '>', 123)
        ->orWhere('email', '!=', null)
    ->close()
    ->where('name', '!=', null);
    ->fetchAll();
```

### Joins

Each join method supports a single conditional. To add additional conditionals, simply precede the join with an `on()` call. For example `on('column1', '=', 'column2', false)`.

#### Inner Join

```php
$users = $record->select()
    ->from('users')
    ->innerJoin('user_groups', 'user_groups.id', '=', 'users.user_group_id', false)
    ->fetchAll();
```

The 5th parameter to ```innerJoin``` tells the join that `users.user_group_id` is a field, not a value. Consider the following, instead:

```php
    ->innerJoin('user_groups', 'user_groups.id', '=', 5)
```

#### Left Join

```php
$users = $record->select()
    ->from('users')
    ->leftJoin('user_groups', 'user_groups.id', '=', 'users.user_group_id', false)
    ->fetchAll();
```

#### Right Join

```php
$users = $record->select()
    ->from('users')
    ->rightJoin('user_groups', 'user_groups.id', '=', 'users.user_group_id', false)
    ->fetchAll();
```

#### Cross Join

```php
$users = $record->select()
    ->from('users')
    ->join('user_groups')
    ->fetchAll();
```

### Subqueries

**Tip:** Avoid these at all costs. Subqueries are incredibly inefficient. This isn't a limitation of this library, rather of the underlying relational database system.

All subqueries start first with the subquery. The idea is to construct the query from the inside out, and as each layer is added the subquery becomes part of the parent query.

```php
$usersQuery = $record->select()
    ->from('users')
    ->where('id', '=', 1234)->get();
$usersValues = $record->values;

$record->reset();

$groups = $record->select()
    ->from('user_groups')
    ->appendValues($usersValues)
    ->innerJoin(array($usersQuery => 'temp'), 'temp.user_group_id', '=', 'user_groups.id', false)
    ->fetchAll();

/*
SELECT * FROM user_groups
INNER JOIN (
    SELECT * FROM users
    WHERE id=1234
) AS temp ON temp.user_group_id=user_groups.id
*/
```

### Insert

#### Simple Insert

```php
$record->insert('users', array('name' => 'Roger Sherman'));
```

#### Insert with Filter

```php
$record->insert(
    'users',
    array('name' => 'Roger Sherman', 'bad_field' => 'will not be inserted'),
    array('name')
);
```

#### On Duplicate

```php
$record->duplicate('name' => 'Roger Sherman')
    ->insert(
        'users',
        array('id' => 1776, 'name' => 'Roger Sherman')
    );
```

#### From a Query

```php
$users = $record->select(array('id'))
    ->from('users');

$record->reset();
$record->insert('some_table', array('id' => $users));
```

### Update

#### Simple Update

```php
$record->where('id', '=', 1776)
    ->update('users', array('name' => 'Roger Sherman'));
```

#### Update with Filter

```php
$record->where('id', '=', 1776)
    ->update(
        'users',
        array('name' => 'Roger Sherman', 'bad_field' => 'will not be updated'),
        array('name')
    );
```

### Delete

#### Simple Delete

```php
$record->from('users')
    ->delete();
```

#### Multi-delete

```php
$record->from('users')
    ->innerJoin('user_groups', 'user_groups.id', '=', 'users.user_group_id', false)
    ->where('user_groups.id', '=', 1)
    ->delete(array('users.*', 'user_groups.*'));
```

### Create Table

```php
/**
* Optionally set the character set and collation of the table being created
* $record->setCharacterSet('utf8mb4');
* $record->setCollation('utf8mb4_unicode_ci');
*/
$record->setField(
        'id',
        array('type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true)
    )
    ->setField('name', array('type' => 'varchar', 'size' => '128'))
    ->setField('emai', 'array('type' => 'varchar', 'size' => '255'))
    ->setKey(array('id'), 'primary')
    ->setKey(array('name'), 'index')
    ->create('users');
```

### Alter Table

```php
$record->setKey(array('name'), 'index', null, false)
    ->alter('users');
```

The 3rd parameter to `setKey` is the name of the index. The 4th parameter identifies whether this is an add or a drop.

### Truncate

```php
$record->truncate('users');
```

### Drop

```php
$record->drop('users');
```

### Transactions

```php
try {
    $record->begin();
    $record->insert('users', array('name' => 'Roger Sherman'));
    $record->commit();
} catch (\PDOException $e) {
    $record->rollBack();
}
```
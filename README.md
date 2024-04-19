# Query

USE AT YOUR OWN RISK!

I would advise against using this on production applications...but feel free to use in your own personal / demo / experimental projects.

[![CodeFactor](https://www.codefactor.io/repository/github/jahidulpabelislam/query/badge)](https://www.codefactor.io/repository/github/jahidulpabelislam/query)
[![Latest Stable Version](https://poser.pugx.org/jpi/query/v/stable)](https://packagist.org/packages/jpi/query)
[![Total Downloads](https://poser.pugx.org/jpi/query/downloads)](https://packagist.org/packages/jpi/query)
[![Latest Unstable Version](https://poser.pugx.org/jpi/query/v/unstable)](https://packagist.org/packages/jpi/query)
[![License](https://poser.pugx.org/jpi/query/license)](https://packagist.org/packages/jpi/query)
![GitHub last commit (branch)](https://img.shields.io/github/last-commit/jahidulpabelislam/query/2.x.svg?label=last%20activity)

A simple library to make querying a database easier, this works as a middle man between the application and a database.

This has been kept very simple stupid (KISS), other than type errors from PHP there is no validation, it will assume you are using it correctly. So please make sure to add your own validation if using user inputs in these queries.

## Dependencies

- PHP 8.0+
- Composer
- PHP PDO
- MySQL 5+
- [jpi/database](https://packagist.org/packages/jpi/database) v2

## Installation

Use [Composer](https://getcomposer.org/)

```bash
$ composer require jpi/query 
```

## Usage

To create an instance, you will need an instance of `\JPI\Database` (if unfamiliar you can read about that [here](https://packagist.org/packages/jpi/database)) which is the first parameter, and the database table name as the second parameter. The same instance can be used multiple times as long as it's for the same database.

```php
$queryBuilder = new \JPI\Database\Query\Builder($database, $table);
```

### Action Methods:

These are the methods to will call to end with `select`, `count: int`, `insert($values array): int|null`, `update($values array): int` & `delete: int`, all are pretty self-explanatory.

### Builder methods

These are all fluent methods, so you can chain them together.

- `table(string $table, string|null $alias)`: if you want to change to another table or didn't set when creating the instance
- `column(string $column, string|null $alias)`:  will select all columns if not set
- `where`:
  - you can pass in whole clause using the first parameter
  - or you can pass column, expression and value separately
- `orderBy(string $column, bool $ascDirection = true)`
- `limit(int $limit, int|null $page)`
- `page(int)`: used to change the offset, only used if `limit` set

### Examples

Assuming a `\JPI\Database\Query\Builder` instance has been created for the `users` database table and set to a variable named `$queryBuilder`.

#### select

This has 4 return types depending on how you use it:

- if you've set `limit` of `1` this will return an associative array of key (column) value pairs or if not found then `null`
- if paged `\JPI\Database\Query\PaginatedResult`
- else `\JPI\Database\Query\Result`

`PaginatedResult` & `Result` work like a normal array just with some extra methods, see https://github.com/jahidulpabelislam/utils?tab=readme-ov-file#collection for more details.

```php
// SELECT * FROM users;
$collection = $queryBuilder->select();
/**
$collection = [
    [
        "id" => 1,
        "first_name" => "Jahidul",
        "last_name" => "Islam",
        "email" => "jahidul@jahidulpabelislam.com",
        "password" => "password123",
        ...
    ],
    [
        "id" => 2,
        "first_name" => "Test",
        "last_name" => "Example",
        "email" => "test@example.com",
        "password" => "password123",
        ...
    ],
    ...
];
*/

// SELECT first_name, last_name FROM users;
$collection = $queryBuilder
    ->column("first_name")
    ->column("last_name")
    ->select();
/**
$collection = [
    [
        "first_name" => "Jahidul",
        "last_name" => "Islam",
    ],
    [
        "first_name" => "Test",
        "last_name" => "Example",
    ],
    ...
];
*/

// SELECT * FROM users WHERE status = "active";
$collection = $queryBuilder->where("status", "=", "active")->select();
/**
$collection = [
    [
        "id" => 1,
        "first_name" => "Jahidul",
        "last_name" => "Islam",
        "email" => "jahidul@jahidulpabelislam.com",
        "password" => "password123",
        "status" => "active",
        ...
    ],
    [
        "id" => 3,
        "first_name" => "Test",
        "last_name" => "Example",
        "email" => "test@example.com",
        "password" => "password123",
        "status" => "active",
        ...
    ],
    ...
];
*/

// SELECT * FROM users WHERE status = "active" ORDER BY last_name ASC;
$collection = $queryBuilder->where("status", "=", "active")->orderBy("last_name")->select();
/**
$collection = [
    [
        "id" => 3,
        "first_name" => "Test",
        "last_name" => "Example",
        "email" => "test@example.com",
        "password" => "password123",
        "status" => "active",
        ...
    ],
    [
        "id" => 1,
        "first_name" => "Jahidul",
        "last_name" => "Islam",
        "email" => "jahidul@jahidulpabelislam.com",
        "password" => "password123",
        "status" => "active",
        ...
    ],
    ...
];
*/

// SELECT * FROM users WHERE status = "active" ORDER BY first_name ASC LIMIT 10 OFFSET 20;
$collection = $queryBuilder->where("status", "=", "active")->orderBy("first_name")->limit(10, 3)->select();
/**
$collection = [
    [
        "id" => 31,
        "first_name" => "Jahidul",
        "last_name" => "Islam",
        "email" => "jahidul@jahidulpabelislam.com",
        "password" => "password123",
        "status" => "active",
        ...
    ],
    [
        "id" => 30,
        "first_name" => "Test",
        "last_name" => "Example",
        "email" => "test@example.com",
        "password" => "password123",
        "status" => "active",
        ...
    ],
    ...
];
*/

// SELECT * FROM users WHERE first_name LIKE "%jahidul%" LIMIT 1;
$row = $queryBuilder->where("first_name", "LIKE", "%jahidul%")->limit(1)->select();
/**
$row = [
    "id" => 1,
    "first_name" => "Jahidul",
    "last_name" => "Islam",
    "email" => "jahidul@jahidulpabelislam.com",
    "password" => "password",
    ...
];
*/
```

#### count

As the name implies this method will just return the count as an integer.

Currently only the `table` & `where` builder methods are supported for this action.

```php
// SELECT COUNT(*) as count FROM users;
$count = $queryBuilder->count();
// $count = 10;

// SELECT COUNT(*) as count FROM users WHERE status = "active";
$count = $queryBuilder->where("status", "=", "active")->count();
// $count = 5;
```

#### insert

This method will just return the id of the row created, unless it failed then `null`.

Currently only the `table` builder method is supported for this action.

```php
// INSERT INTO users SET first_name= "Jahidul", last_name= "Islam", email = "jahidul@jahidulpabelislam.com", password = "password";
$id = $queryBuilder->insert([
    "first_name" => "Jahidul",
    "last_name" => "Islam",
    "email" => "jahidul@jahidulpabelislam.com",
    "password" => "password",
]);
// $id = 1;
```

#### update

This method will return the count of how many rows have been updated by the query.

Currently only the `table` & `where` builder methods are supported for this action.

```php
// UPDATE users SET status = "inactive";
$numberOrRowsUpdated = $queryBuilder->update([
    "status" => "inactive",
]);
// $numberOrRowsUpdated = 10;

// UPDATE users SET first_name = "Pabel" WHERE id = 1;
$numberOrRowsUpdated = $queryBuilder
    ->where("id", "=", 1)
    ->update([
        "first_name" => "Pabel",
    ])
;
// $numberOrRowsUpdated = 1;
```

#### delete

This method will return the count of how many rows have been deleted by the query.

Currently only the `table` & `where` builder methods are supported for this action.

```php
// DELETE FROM users;
$numberOrRowsDeleted = $queryBuilder->delete();
// $numberOrRowsDeleted = 10;

// DELETE FROM users WHERE id = 1;
$numberOrRowsDeleted = $queryBuilder->where("id", "=", 1)->delete();
// $numberOrRowsDeleted = 1;
```

## Support

If you found this library interesting or useful please do spread the word of this library: share on your social's, star on GitHub, etc.

If you find any issues or have any feature requests, you can open an [issue](https://github.com/jahidulpabelislam/query/issues) or can email [me @ jahidulpabelislam.com](mailto:me@jahidulpabelislam.com) :smirk:.

## Authors

- [Jahidul Pabel Islam](https://jahidulpabelislam.com/) [<me@jahidulpabelislam.com>](mailto:me@jahidulpabelislam.com)

## Licence

This module is licensed under the General Public Licence - see the [licence](LICENSE.md) file for details.

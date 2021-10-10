# Query

[![CodeFactor](https://www.codefactor.io/repository/github/jahidulpabelislam/query/badge)](https://www.codefactor.io/repository/github/jahidulpabelislam/query)
[![Latest Stable Version](https://poser.pugx.org/jpi/query/v/stable)](https://packagist.org/packages/jpi/query)
[![Total Downloads](https://poser.pugx.org/jpi/query/downloads)](https://packagist.org/packages/jpi/query)
[![Latest Unstable Version](https://poser.pugx.org/jpi/query/v/unstable)](https://packagist.org/packages/jpi/query)
[![License](https://poser.pugx.org/jpi/query/license)](https://packagist.org/packages/jpi/query)
![GitHub last commit (branch)](https://img.shields.io/github/last-commit/jahidulpabelislam/query/master.svg?label=last%20activity)

A very very simple query builder library, this works as a middle man between the application and a database.

This has been kept very simple stupid (KISS), there is no validation, it will assume you are using it correctly.

I WOULD ADVISE AGAINST USING THIS ON PRODUCTION APPLICATIONS...feel free to use in your own personal / demo / experimental projects.

So use at your own risk.

## Dependencies

- PHP 7.1+
- Composer
- PHP PDO
- MySQL 5+
- [jpi/database](https://packagist.org/packages/jpi/database)

## Installation

Use [Composer](https://getcomposer.org/)

```bash
$ composer require jpi/query 
```

## Usage

Assuming you have knowledge on `jpi/database` (if not you can read [here](https://packagist.org/packages/jpi/database)), and that `$connection` is an instance of `JPI\Database\Connection`.

Create an instance, for the first parameter you will need to pass an instance of `JPI\Database\Connection`, and the 2nd is the database table name. The same instance can be used multiple times as long as the table is the same.

```php
$query = new \JPI\Database\Query($connection, 'users');
```

### Available Methods:

- select: `($columns, $where, $params, $orderBy, $limit, $page)`
- count: `($where, $params)`
- insert: `($values)`
- update: `($values, $where, $params)`
- delete: `($where, $params)`

The params type should be consistent where the param name is the same.

#### Params

- `$columns`: Array of strings, single string, or null (defaults to `"*"`)
- `$where`: Array of strings, single string, integer (assumes where is for `id` column) or null (default)
- `$params`: Associative array, or null (default)
- `$orderBy`: Array of strings, single string, or null (default)
- `$limit`: Integer or null (default)
- `$page`: Integer or null (default) (Only used of `$limit` is passed)
- `$values`: Associative array

#### Examples

(Assuming a `JPI\Database\Query` instance has been created and set to a variable named `$query`)

##### select

```php
$collection = $query->select();

$collection = $query->select([
    "first_name",
    "last_name",
]);

$collection = $query->select(
    "*",
    "status = :status",
    [
        "status" => "active",
    ],
);

$collection = $query->select(
    "*",
    "status = :status",
    [
        "status" => "active",
    ],
    "first_name"
);

$collection = $query->select(
    "*",
    "status = :status",
    [
        "status" => "active",
    ],
    "first_name",
    10,
    1
);

$row = $query->select(
    "*",
    "first_name LIKE :first_name",
    [
        "first_name" => "%jahidul%",
    ],
    null,
    1
);
```

##### count

```php
$count = $query->count();

$count = $query->count(
    "status = :status",
    [
        "status" => "active",
    ]
);
```

##### insert

```php
$id = $query->insert([
        "first_name" => "Jahidul",
        "last_name" => "Islam",
        "email" => "jahidul@jahidulpabelislam.com,
        "password" => "password",
    ]);
    
/**
$id = 1;
*/
```

##### update

```php
$numberOrRowsUpdated = $query->update(
    [
        "first_name" => "Pabel",
    ],
    ["id = :id"],
    ["id" => 1]
);

/**
$numberOrRowsUpdated = 1;
*/
```

##### delete

```php
$numberOrRowsDeleted = $query->delete(["id = :id"], ["id" => 1]);

/**
$numberOrRowsDeleted = 1;
*/
```

## Changelog

See [CHANGELOG](CHANGELOG.md)

## Support

If you found this library interesting or useful please do spread the word of this library: share on your social's, star on GitHub, etc.

If you find any issues or have any feature requests, you can open an [issue](https://github.com/jahidulpabelislam/query/issues) or can email [me @ jahidulpabelislam.com](mailto:me@jahidulpabelislam.com) :smirk:.

## Authors

-   [Jahidul Pabel Islam](https://jahidulpabelislam.com/) [<me@jahidulpabelislam.com>](mailto:me@jahidulpabelislam.com)

## License

This module is licensed under the General Public License - see the [License](LICENSE.md) file for details

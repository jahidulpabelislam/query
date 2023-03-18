# Query

[![CodeFactor](https://www.codefactor.io/repository/github/jahidulpabelislam/query/badge)](https://www.codefactor.io/repository/github/jahidulpabelislam/query)
[![Latest Stable Version](https://poser.pugx.org/jpi/query/v/stable)](https://packagist.org/packages/jpi/query)
[![Total Downloads](https://poser.pugx.org/jpi/query/downloads)](https://packagist.org/packages/jpi/query)
[![Latest Unstable Version](https://poser.pugx.org/jpi/query/v/unstable)](https://packagist.org/packages/jpi/query)
[![License](https://poser.pugx.org/jpi/query/license)](https://packagist.org/packages/jpi/query)
![GitHub last commit (branch)](https://img.shields.io/github/last-commit/jahidulpabelislam/query/1.x.svg?label=last%20activity)

A very very simple library to make querying a database easier, this works as a middle man between the application and a database.

This has been kept very simple stupid (KISS), there is no validation, it will assume you are using it correctly. So please make sure to add your own validation if using user inputs in these queries.

I WOULD ADVISE AGAINST USING THIS ON PRODUCTION APPLICATIONS...but feel free to use in your own personal / demo / experimental projects.

Use at your own risk.

## Dependencies

- PHP 8.0+
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

To create an instance, you will need an instance of `\JPI\Database\Connection` (if unfamiliar you can read about that [here](https://packagist.org/packages/jpi/database)) which is the first parameter, and the database table name as the second parameter. The same instance can be used multiple times as long as the table is the same.

```php
$query = new \JPI\Database\Query($connection, $table);
```

### Available Methods:

All the methods are self-explanatory.

- select - To get a collection of rows or a single row in the table. Params: `$columns`, `$where`, `$params`, `$orderBy`, `$limit` & `$page` (All optional)
- count - To get total count of rows in the table. Params: `$where`  & `$params` (All optional)
- insert - To insert a new row in the table. Params: `$values`
- update - To update values for row(s) in the table. Params: `$values`, `$where` & `$params` (`$where` & `$params` are optional)
- delete - To delete row(s) from the table. Params: `$where` & `$params` (All optional)

The params type should be consistent where the name is the same.

#### Params

- `$columns`: The columns to get in the select query. Type: Array of strings, single string, or null (default: `"*"`)
- `$where`: The where clauses for query. Type: Array of strings, single string, integer (assumes where is for `id` column) or `null` (default: `null`)
- `$params`: The key values pairs to bind for query. Type: Associative array, or `null` (default: `null`)
- `$orderBy`: The order by clauses for the select query. Type: Array of strings, single string, or `null` (default: `null`)
- `$limit`: The limit for the select query. Type: Integer or `null` (default: `null`)
- `$page`: . The page number if limited, Used to calculate the offset. Type: Integer or null (default: `null`) (Only used of `$limit` is passed, then default: `1`)
- `$values`: The key values pairs for insert or update. Type: Associative array

#### Examples

Assuming a `JPI\Database\Query` instance has been created for the `users` database table and set to a variable named `$query`.

##### select

A `select` has 3 three return types depending on how you use it.

- `limit = 1` OR `$where` is an integer, this will return an associative array of key (column) value pairs. UNLESS none is found then `null` is returned
- an `\JPI\Database\Collection` is returned if it is a paginated multi row select
- else a two-dimensional array is returned for other multi row selects

A `\JPI\Database\Collection` works like a normal array just with some extra methods:
- `isset(int $key)` check if item exists by key
- `get(int $key)` to get an item by key
- `getCount()` get the count of rows in the collection
- `getTotalCount()` get the TOTAL count of rows (the count without the LIMIT)
- `getLimit()` get the LIMIT used in query
- `getPage()` get the page number from query

```php
// SELECT * FROM users;
$collection = $query->select();
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
$collection = $query->select([
    "first_name",
    "last_name",
]);
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
$collection = $query->select(
    "*",
    "status = :status",
    [
        "status" => "active",
    ],
);
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

// SELECT * FROM users WHERE status = "active" ORDER BY last_name;
$collection = $query->select(
    "*",
    "status = :status",
    [
        "status" => "active",
    ],
    "last_name"
);
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

// SELECT * FROM users WHERE status = "active" ORDER BY first_name LIMIT 10 OFFSET 20;
$collection = $query->select(
    "*",
    "status = :status",
    [
        "status" => "active",
    ],
    "first_name",
    10,
    2
);
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
$row = $query->select(
    "*",
    "first_name LIKE :first_name",
    [
        "first_name" => "%jahidul%",
    ],
    null,
    1
);
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

##### count

As the name implies this method will just return the count as an integer.

```php
// SELECT COUNT(*) FROM users;
$count = $query->count();
// $count = 10;

// SELECT COUNT(*) FROM users WHERE status = "active";
$count = $query->count(
    "status = :status",
    [
        "status" => "active",
    ]
);
// $count = 5;
```

##### insert

This method will just return the id of the row created, unless it failed then `null`

```php
// INSERT INTO users (first_name, last_name, email, password) VALUES ("Jahidul", "Islam", "jahidul@jahidulpabelislam.com", "password");"
$id = $query->insert([
    "first_name" => "Jahidul",
    "last_name" => "Islam",
    "email" => "jahidul@jahidulpabelislam.com",
    "password" => "password",
]);
// $id = 1;
```

##### update

This method will return the count of how many rows have been updated by the query.

```php
// UPDATE users SET status = "inactive";
$numberOrRowsUpdated = $query->update(
    [
        "status" => "inactive",
    ]
);
// $numberOrRowsUpdated = 10;

// UPDATE users SET first_name = "Pabel" WHERE id = 1;
$numberOrRowsUpdated = $query->update(
    [
        "first_name" => "Pabel",
    ],
    ["id = :id"],
    ["id" => 1]
);
// $numberOrRowsUpdated = 1;
```

##### delete

This method will return the count of how many rows have been deleted by the query.

```php
// DELETE FROM users;
$numberOrRowsDeleted = $query->delete();
// $numberOrRowsDeleted = 10;

// DELETE FROM users WHERE id = 1;
$numberOrRowsDeleted = $query->delete(["id = :id"], ["id" => 1]);
// $numberOrRowsDeleted = 1;
```

## Changelog

See [CHANGELOG](CHANGELOG.md)

## Support

If you found this library interesting or useful please do spread the word of this library: share on your social's, star on GitHub, etc.

If you find any issues or have any feature requests, you can open an [issue](https://github.com/jahidulpabelislam/query/issues) or can email [me @ jahidulpabelislam.com](mailto:me@jahidulpabelislam.com) :smirk:.

## Authors

- [Jahidul Pabel Islam](https://jahidulpabelislam.com/) [<me@jahidulpabelislam.com>](mailto:me@jahidulpabelislam.com)

## License

This module is licensed under the General Public License - see the [License](LICENSE.md) file for details

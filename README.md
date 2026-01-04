# Query

[![CodeFactor](https://www.codefactor.io/repository/github/jahidulpabelislam/query/badge)](https://www.codefactor.io/repository/github/jahidulpabelislam/query)
[![Latest Stable Version](https://poser.pugx.org/jpi/query/v/stable)](https://packagist.org/packages/jpi/query)
[![Total Downloads](https://poser.pugx.org/jpi/query/downloads)](https://packagist.org/packages/jpi/query)
[![Latest Unstable Version](https://poser.pugx.org/jpi/query/v/unstable)](https://packagist.org/packages/jpi/query)
[![License](https://poser.pugx.org/jpi/query/license)](https://packagist.org/packages/jpi/query)
![GitHub last commit (branch)](https://img.shields.io/github/last-commit/jahidulpabelislam/query/2.x.svg?label=last%20activity)

A simple and lightweight SQL query builder library to make querying a database easier. It works as a middleman between your application and the database.

This has been kept very simple stupid (KISS), with minimal validation (PHP type errors only) to reduce complexity in the library and maximize performance for consumer developers. Therefore, please make sure to add your own validation if using user inputs in these queries.

## Features

- Fluent, chainable query builder with simple, expressive syntax
- Support for `SELECT`, `INSERT`, `UPDATE`, and `DELETE` queries
- Support for columns, joins (`INNER`, `LEFT`, and `RIGHT`), where clauses, ordering, limiting, and paging
- Returns convenient collections for `SELECT` queries

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

To create an instance, you will need an instance of `\JPI\Database` (which is an extension of `PDO` - you can find out more [here](https://packagist.org/packages/jpi/database)) which is the first parameter, and the database table name as the second parameter. The same instance can be used multiple times as long as it's for the same database.

```php
$queryBuilder = new \JPI\Database\Query\Builder($database, $table);
```

### Action Methods:

These are the methods to call to end with `select`, `count`, `insert`, `update` & `delete`, all are pretty self-explanatory.

### Builder methods

These are all fluent methods, so you can chain them together.

#### `table()`

If you want to change to another table or didn't set when creating the instance.

```php
table(string $table, string|null $alias): static
```

#### `column()`

Will select all columns if not set.

```php
column(string $column, string|null $alias): static
```

#### `join()`

- `$joinOrTable`: instance of `\JPI\Database\Query\Clause\Join` or the table name as string, use the class if you want multiple expressions in the `ON` clause
- `$type`: `INNER` (default), `LEFT` or `RIGHT`, usually you can leave blank, and use `rightJoin` or `leftJoin` methods

```php
join(JoinClause|string $joinOrTable, string|null $on, string $type = "INNER"): static
```

#### `where()`

Adds a WHERE condition to the query. This method is very flexible and supports multiple calling patterns:

```php
where(string|Stringable $columnOrExpression, ?string $operator, mixed $valueOrPlaceholder): static
```

**Raw SQL expression**: Pass a complete SQL expression as the first parameter only

```php
->where("status = 'active'")
->where("created_at > NOW()")
```

**Column, operator, value**: Pass column name, operator, and value separately (recommended for security as it uses parameterized queries)

```php
->where("status", "=", "active")
->where("age", ">", 18)
->where("name", "LIKE", "%john%")
```

**Supported operators**: `=`, `!=`, `<>`, `<`, `>`, `<=`, `>=`, `LIKE`, `IN`, `NOT IN`, `BETWEEN`

**Array values**: When passing an array as the value, the operator is automatically set to `IN` (or `NOT IN` if specified)

```php
->where("status", "IN", ["active", "pending"])
->where("id", "NOT IN", [1, 2, 3])
```

**BETWEEN operator**: Pass an array with exactly 2 values for the BETWEEN operator

```php
->where("age", "BETWEEN", [18, 65])
// Generates: WHERE age BETWEEN :age_1 AND :age_2
```

**Subqueries**: Pass a Builder instance as the value to use a subquery

```php
$subquery = new \JPI\Database\Query\Builder($database, "orders");
$subquery->column("customer_id")->where("status", "=", "completed");
->where("id", "IN", $subquery)
// Generates: WHERE id IN (SELECT customer_id FROM orders WHERE status = :status)
```

**Complex conditions**: Pass an `AndCondition` or `OrCondition` instance to create complex nested conditions (see below)

**Note**: All values (except raw SQL expressions) are automatically parameterized to prevent SQL injection

#### `orderBy()`

```php
orderBy(string $column, bool $ascDirection = true): static
```

#### `limit()`

```php
limit(int $limit, int|null $page): static
```

#### `page()`

Used to change the offset, only used if `limit` set.

```php
page(int $page): static
```

#### Complex WHERE Conditions

For more complex WHERE clauses that require OR logic or nested conditions, you can use `AndCondition` and `OrCondition` classes.

##### AndCondition

`AndCondition` groups multiple conditions together with AND logic. Create one using `$queryBuilder->newAndCondition()`.

```php
// Create an AND condition
$andCondition = $queryBuilder->newAndCondition()
    ->where("status", "=", "active")
    ->where("age", ">", 18);

// Use it in a WHERE clause
$queryBuilder->where($andCondition);
// Generates: WHERE (status = :status AND age > :age)
```

##### OrCondition

`OrCondition` groups multiple conditions together with OR logic. Create one using `$queryBuilder->newOrCondition()`.

```php
// Create an OR condition
$orCondition = $queryBuilder->newOrCondition()
    ->where("status", "=", "active")
    ->where("status", "=", "pending");

// Use it in a WHERE clause
$queryBuilder->where($orCondition);
// Generates: WHERE (status = :status OR status = :status_1)
```

##### Combining AND and OR Conditions

You can nest `AndCondition` and `OrCondition` to create complex logic:

```php
// Complex example: (status = 'active' AND age > 18) OR (status = 'premium')
$queryBuilder
    ->where(
        $queryBuilder->newOrCondition()
            ->where(
                $queryBuilder->newAndCondition()
                    ->where("status", "=", "active")
                    ->where("age", ">", 18)
            )
            ->where("status", "=", "premium")
    );
// Generates: WHERE ((status = :status AND age > :age) OR status = :status_1)
```

```php
// Another example: status = 'active' AND (role = 'admin' OR role = 'moderator')
$queryBuilder
    ->where("status", "=", "active")
    ->where(
        $queryBuilder->newOrCondition()
            ->where("role", "=", "admin")
            ->where("role", "=", "moderator")
    );
// Generates: WHERE status = :status AND (role = :role OR role = :role_1)
```

**Note**: By default, multiple `where()` calls on the builder are combined with AND logic. The main `where()` clause itself is an `AndCondition`, which is why you can chain multiple `where()` calls.

#### WhereableTrait

The `WhereableTrait` provides the core functionality for the `where()` method and is used by:
- `Builder` class - for the main query WHERE clause
- `AndCondition` class - for grouping conditions with AND logic
- `OrCondition` class - for grouping conditions with OR logic

Key features handled by `WhereableTrait`:
- **Automatic parameter binding**: All values are automatically bound as PDO parameters to prevent SQL injection
- **Operator-specific logic**:
    - `IN` and `NOT IN`: Automatically handles arrays by creating multiple parameters
    - `BETWEEN`: Expects a 2-element array and generates `BETWEEN :param_1 AND :param_2`
    - Single-element arrays are converted to use `=` or `<>` operators
- **Subquery support**: Accepts `Builder` instances as values and extracts their SELECT query
- **Raw SQL expressions**: When only the first parameter is passed, it's treated as a raw SQL expression (use with caution)

### Examples

Assuming a `\JPI\Database\Query\Builder` instance has been created for the `users` database table and set to a variable named `$queryBuilder`.

#### select

This has 4 return types depending on how you use it:

- if you've set `limit` of `1` this will return an instance of `\JPI\Database\Query\Result\Row` or `null` if not found. The `Row` class can be used as an associative array of key (column) value pairs
- if paged/limited and the `withPagination` param (first param) isn't `false` then `\JPI\Database\Query\Result\PaginatedCollection`
- else `\JPI\Database\Query\Result\Collection`

`PaginatedCollection` & `Collection` work like a normal array just with some extra methods, see https://github.com/jahidulpabelislam/utils?tab=readme-ov-file#collection for more details. Both of these contain multiple instances of `Row`. `PaginatedCollection` has meta data on the limit used, page number and total count if not limited, and the collection is immutable.

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
$collection = $queryBuilder
    ->where("status", "=", "active")
    ->select();
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
$collection = $queryBuilder
    ->where("status", "=", "active")
    ->orderBy("last_name")
    ->select();
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
$collection = $queryBuilder
    ->where("status", "=", "active")
    ->orderBy("first_name")
    ->limit(10, 3)
    ->select();
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
$row = $queryBuilder
    ->where("first_name", "LIKE", "%jahidul%")
    ->limit(1)
    ->select();
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

/**
SELECT * FROM users
INNER JOIN user_logins ON user_id = login_user_user_id;
*/
$queryBuilder->join("user_logins", "user_id = login_user_user_id");
$collection = $queryBuilder->select();
/**
$collection = [
    [
        "id" => 1,
        "first_name" => "Jahidul",
        "login_user_id" => 1,
        "login_user_user_id" => 1,
        "login_user_date" => "2025-10-29 10:00:00",
        ...
    ],
    [
        "id" => 1,
        "first_name" => "Jahidul",
        "login_user_id" => 2,
        "login_user_user_id" => 1,
        "login_user_date" => "2025-11-01 12:00:00",
        ...
    ],
];

/**
SELECT * FROM users
INNER JOIN user_logins ON user_id = login_user_user_id AND login_user_date > '2025-11-01';
 */
$queryBuilder->join(
    $queryBuilder->newJoinClause("user_logins")
        ->on("user_id = login_user_user_id")
        ->on("login_user_date > '2025-11-01'")
);
$queryBuilder->select();
/**
$collection = [
    [
        "id" => 1,
        "first_name" => "Jahidul",
        "login_user_id" => 2,
        "login_user_user_id" => 1,
        "login_user_date" => "2025-11-01 12:00:00",
        ...
    ],
];
```

##### More WHERE Examples

```php
// Using IN operator with array
// SELECT * FROM users WHERE status IN ('active', 'pending');
$collection = $queryBuilder
    ->where("status", "IN", ["active", "pending"])
    ->select();

// Using BETWEEN operator
// SELECT * FROM users WHERE age BETWEEN 18 AND 65;
$collection = $queryBuilder
    ->where("age", "BETWEEN", [18, 65])
    ->select();

// Using LIKE operator
// SELECT * FROM users WHERE email LIKE '%@example.com';
$collection = $queryBuilder
    ->where("email", "LIKE", "%@example.com")
    ->select();

// Using OR conditions
// SELECT * FROM users WHERE (status = 'active' OR status = 'pending');
$collection = $queryBuilder
    ->where(
        $queryBuilder->newOrCondition()
            ->where("status", "=", "active")
            ->where("status", "=", "pending")
    )
    ->select();

// Complex nested conditions
// SELECT * FROM users WHERE status = 'active' AND (role = 'admin' OR role = 'moderator') AND age > 18;
$collection = $queryBuilder
    ->where("status", "=", "active")
    ->where(
        $queryBuilder->newOrCondition()
            ->where("role", "=", "admin")
            ->where("role", "=", "moderator")
    )
    ->where("age", ">", 18)
    ->select();

// Using subquery
// SELECT * FROM users WHERE id IN (SELECT customer_id FROM orders WHERE status = 'completed');
$subquery = new \JPI\Database\Query\Builder($database, "orders");
$subquery->column("customer_id")->where("status", "=", "completed");

$collection = $queryBuilder
    ->where("id", "IN", $subquery)
    ->select();
```

#### count

As the name implies this method will just return the count as an integer. By default it will do `COUNT(*)`, but you can pass a column name or expression as the first parameter to count non-NULL values in a specific column or use expressions like `DISTINCT`.

For obvious reasons only the `table` & `where` builder methods are supported for this action.

```php
// SELECT COUNT(*) as count FROM users;
$count = $queryBuilder->count();
// $count = 10;

// SELECT COUNT(*) as count FROM users WHERE status = "active";
$count = $queryBuilder
    ->where("status", "=", "active")
    ->count();
// $count = 5;

// SELECT COUNT(email) as count FROM users;
// Using column parameter to count non-NULL values in the email column
$count = $queryBuilder->count("email");
// $count = 10;

// SELECT COUNT(DISTINCT status) as count FROM users;
// Can use expressions in the column parameter
$count = $queryBuilder->count("DISTINCT status");
// $count = 2;
```

#### insert

This method will just return the id of the row created unless it fails then `null`.

Only the `table` builder method is supported for this action.

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

`column` & `page` builder methods aren't supported for this action.

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
    ]);
// $numberOrRowsUpdated = 1;
```

#### delete

This method will return the count of how many rows have been deleted by the query.

`column` & `page` builder methods aren't supported for this action.

```php
// DELETE FROM users;
$numberOrRowsDeleted = $queryBuilder->delete();
// $numberOrRowsDeleted = 10;

// DELETE FROM users WHERE id = 1;
$numberOrRowsDeleted = $queryBuilder
    ->where("id", "=", 1)
    ->delete();
// $numberOrRowsDeleted = 1;
```

## Support

If you found this library interesting or useful please spread the word about this library: share on your socials, star on GitHub, etc.

If you find any issues or have any feature requests, you can open an [issue](https://github.com/jahidulpabelislam/query/issues) or email [me @ jahidulpabelislam.com](mailto:me@jahidulpabelislam.com) :smirk:.

## Authors

- [Jahidul Pabel Islam](https://jahidulpabelislam.com/) [<me@jahidulpabelislam.com>](mailto:me@jahidulpabelislam.com)

## Licence

This module is licensed under the General Public Licence - see the [licence](LICENSE.md) file for details.

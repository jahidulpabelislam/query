<?php

declare(strict_types=1);

namespace JPI\Database\Query\Tests;

use JPI\Database;
use JPI\Database\Query\Builder;
use JPI\Database\Query\Result\Collection;
use JPI\Database\Query\Result\PaginatedCollection;
use JPI\Database\Query\Result\Row;
use PHPUnit\Framework\MockObject\Stub;

/**
 * @covers \JPI\Database\Query\Builder
 * @covers \JPI\Database\Query\Clause\Join
 * @covers \JPI\Database\Query\Clause\OrderBy
 * @covers \JPI\Database\Query\Clause\Where
 * @covers \JPI\Database\Query\Clause\Where\AndCondition
 * @covers \JPI\Database\Query\Clause\Where\OrCondition
 * @covers \JPI\Database\Query\DelegatedParamableTrait
 * @covers \JPI\Database\Query\ParamableTrait
 * @covers \JPI\Database\Query\WhereableTrait
 */
final class BuilderTest extends BaseTestCase {

    protected function createDatabase(): Database&Stub {
        $database = $this->createStub(Database::class);
        $database->method("selectAll")
            ->willReturn([
                ["first_name" => "John", "last_name" => "Doe"],
                ["first_name" => "Jane", "last_name" => "Smith"],
            ])
        ;

        $database->method("selectFirst")
            ->willReturnCallback(function (string $query, array $params) {
                if (str_contains($query, "as count")) {
                    return ["count" => 2];
                }
                return ["first_name" => "John", "last_name" => "Doe"];
            })
        ;

        return $database;
    }

    public function testSelectBuilding(): void {
        $builder = new Builder($this->createDatabase(), "table_one");

        // Just select all
        $this->assertSame(
            "SELECT *
FROM table_one;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // Changing table
        $builder->table("users");
        $this->assertSame(
            "SELECT *
FROM users;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // Single column
        $builder->column("email");
        $this->assertSame(
            "SELECT email
FROM users;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // + another column with an alias
        $builder->column("first_name", "name");
        $this->assertSame(
            "SELECT email,first_name as name
FROM users;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // + single where clause
        $builder->where("status", "=", "active");
        $this->assertSame(
            "SELECT email,first_name as name
FROM users
WHERE status = :status;",
            $builder->getSelectQuery()
        );
        $this->assertSame(["status" => "active"], $builder->getParams());

        // + another where clause
        $builder->where("age", ">", 18);
        $this->assertSame(
            "SELECT email,first_name as name
FROM users
WHERE status = :status AND age > :age;",
            $builder->getSelectQuery()
        );
        $this->assertSame(
            [
                "status" => "active",
                "age" => 18,
            ],
            $builder->getParams()
        );

        // + inner OR where
        $builder->where(
            $builder->newOrCondition()
                ->where("role", "=", "admin")
                ->where("type", "=", "premium")
        );
        $this->assertSame(
            "SELECT email,first_name as name
FROM users
WHERE status = :status AND age > :age AND (role = :role OR type = :type);",
            $builder->getSelectQuery()
        );
        $this->assertSame(
            [
                "status" => "active",
                "age" => 18,
                "role" => "admin",
                "type" => "premium",
            ],
            $builder->getParams()
        );

        // Order by
        $builder = $this->createBuilder();
        $builder->orderBy("created_at");
        $this->assertSame(
            "SELECT *
FROM users
ORDER BY created_at ASC;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // + another order by
        $builder->orderBy("last_name", false);
        $this->assertSame(
            "SELECT *
FROM users
ORDER BY created_at ASC, last_name DESC;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // Limit
        $builder = $this->createBuilder();
        $builder->limit(5);
        $this->assertSame(
            "SELECT *
FROM users
LIMIT 5;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // Limit + page
        $builder->limit(5, 2);
        $this->assertSame(
            "SELECT *
FROM users
LIMIT 5 OFFSET 5;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // With an inner join
        $builder = $this->createBuilder();
        $builder->join("orders", "users.id = orders.user_id");
        $this->assertSame(
            "SELECT *
FROM users
INNER JOIN orders ON users.id = orders.user_id;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // With 2 ON conditions on an inner join
        $builder = $this->createBuilder();
        $builder->join(
            $builder->newJoinClause("orders")
                ->on("users.id = orders.user_id")
                ->on("orders.status = 'completed'")
        );
        $this->assertSame(
            "SELECT *
FROM users
INNER JOIN orders ON users.id = orders.user_id AND orders.status = 'completed';",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // With a right join - using helper/alias method
        $builder = $this->createBuilder();
        $builder->rightJoin("orders", "users.id = orders.user_id");
        $this->assertSame(
            "SELECT *
FROM users
RIGHT JOIN orders ON users.id = orders.user_id;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // With a left join - using helper/alias method
        $builder = $this->createBuilder();
        $builder->leftJoin("orders", "users.id = orders.user_id");
        $this->assertSame(
            "SELECT *
FROM users
LEFT JOIN orders ON users.id = orders.user_id;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // 2 joins
        $builder = $this->createBuilder();
        $builder->join("orders", "users.id = orders.user_id");
        $builder->leftJoin("profiles", "users.id = profiles.user_id");
        $this->assertSame(
            "SELECT *
FROM users
INNER JOIN orders ON users.id = orders.user_id
LEFT JOIN profiles ON users.id = profiles.user_id;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());
    }

    public function testSelectOne(): void {
        // With limit 1 always returns single result
        $result = $this->createBuilder()
            ->limit(1)
            ->select();
        $this->assertInstanceOf(Row::class, $result);
    }

    public function testSelectAll(): void {
        // Without limit always returns Collection
        $result = $this->createBuilder()->select(false);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertNotInstanceOf(PaginatedCollection::class, $result);
    }

    public function testSelectWithPagination(): void {
        // withPagination true (Default behavior)
        $result = $this->createBuilder()
            ->limit(2)
            ->select();
        $this->assertInstanceOf(PaginatedCollection::class, $result);
    }

    public function testSelectWithPaginationFalse(): void {
        $database = parent::createDatabase();

        // Should call selectAll but not selectFirst (which count() uses internally)
        $database->expects($this->once())->method("selectAll");
        $database->expects($this->never())->method("selectFirst");

        // When withPagination is false, should return Collection instead of PaginatedCollection
        $result = $this->createBuilder($database)
            ->limit(2)
            ->select(false);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertNotInstanceOf(PaginatedCollection::class, $result);
    }
}

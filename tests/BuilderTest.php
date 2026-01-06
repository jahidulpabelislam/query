<?php

declare(strict_types=1);

namespace JPI\Database\Query\Tests;

use JPI\Database;
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
                ["column_one" => "Value 11", "column_two" => "Value 12"],
                ["column_one" => "Value 21", "column_two" => "Value 22"],
            ])
        ;

        $database->method("selectFirst")
            ->willReturnCallback(function (string $query, array $params) {
                if (str_contains($query, "as count")) {
                    return ["count" => 2];
                }
                return ["column_one" => "Value 11", "column_two" => "Value 12"];
            })
        ;

        return $database;
    }

    public function testSelectBuilding(): void {
        $builder = $this->createBuilder();

        // Just select all
        $this->assertSame(
            "SELECT *
FROM users;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // Changing table
        $builder->table("table");
        $this->assertSame(
            "SELECT *
FROM table;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // Single column
        $builder->column("column");
        $this->assertSame(
            "SELECT column
FROM table;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // + another column with an alias
        $builder->column("column_two", "column_two_alias");
        $this->assertSame(
            "SELECT column,column_two as column_two_alias
FROM table;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // + single where clause
        $builder->where("column_one", "=", 1);
        $this->assertSame(
            "SELECT column,column_two as column_two_alias
FROM table
WHERE column_one = :column_one;",
            $builder->getSelectQuery()
        );
        $this->assertSame(["column_one" => 1], $builder->getParams());

        // + another where clause
        $builder->where("column_two", "=", 2);
        $this->assertSame(
            "SELECT column,column_two as column_two_alias
FROM table
WHERE column_one = :column_one AND column_two = :column_two;",
            $builder->getSelectQuery()
        );
        $this->assertSame(
            [
                "column_one" => 1,
                "column_two" => 2,
            ],
            $builder->getParams()
        );

        // + inner OR where
        $builder->where(
            $builder->newOrCondition()
                ->where("column_three", "=", 3)
                ->where("column_four", "=", 4)
        );
        $this->assertSame(
            "SELECT column,column_two as column_two_alias
FROM table
WHERE column_one = :column_one AND column_two = :column_two AND (column_three = :column_three OR column_four = :column_four);",
            $builder->getSelectQuery()
        );
        $this->assertSame(
            [
                "column_one" => 1,
                "column_two" => 2,
                "column_three" => 3,
                "column_four" => 4,
            ],
            $builder->getParams()
        );

        // Order by
        $builder = $this->createBuilder();
        $builder->orderBy("column_one");
        $this->assertSame(
            "SELECT *
FROM users
ORDER BY column_one ASC;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // + another order by
        $builder->orderBy("column_two", false);
        $this->assertSame(
            "SELECT *
FROM users
ORDER BY column_one ASC, column_two DESC;",
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
        $builder->join("table_two", "column_one = column_two");
        $this->assertSame(
            "SELECT *
FROM users
INNER JOIN table_two ON column_one = column_two;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // With 2 ON conditions on an inner join
        $builder = $this->createBuilder();
        $builder->join(
            $builder->newJoinClause("table_two")
                ->on("column_one = column_two")
                ->on("column_three = column_four")
        );
        $this->assertSame(
            "SELECT *
FROM users
INNER JOIN table_two ON column_one = column_two AND column_three = column_four;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // With a right join - using helper/alias method
        $builder = $this->createBuilder();
        $builder->rightJoin("table_two", "column_one = column_two");
        $this->assertSame(
            "SELECT *
FROM users
RIGHT JOIN table_two ON column_one = column_two;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // With a left join - using helper/alias method
        $builder = $this->createBuilder();
        $builder->leftJoin("table_two", "column_one = column_two");
        $this->assertSame(
            "SELECT *
FROM users
LEFT JOIN table_two ON column_one = column_two;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());

        // 2 joins
        $builder = $this->createBuilder();
        $builder->join("table_two", "column_one = column_two");
        $builder->leftJoin("table_three", "column_one = column_two");
        $this->assertSame(
            "SELECT *
FROM users
INNER JOIN table_two ON column_one = column_two LEFT JOIN table_three ON column_one = column_two;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($builder->getParams());
    }

    public function testSelectOne(): void {
        // With limit 1 always returns single result
        $result = $this->createBuilder()
            ->limit(1)
            ->select();
        $this->assertInstanceOf(\JPI\Database\Query\Result\Row::class, $result);
    }

    public function testSelectAll(): void {
        // Without limit always returns Collection
        $result = $this->createBuilder()->select(false);
        $this->assertInstanceOf(\JPI\Database\Query\Result\Collection::class, $result);
        $this->assertNotInstanceOf(\JPI\Database\Query\Result\PaginatedCollection::class, $result);
    }

    public function testSelectWithPagination(): void {
        // withPagination true (Default behavior)
        $result = $this->createBuilder()
            ->limit(2)
            ->select();
        $this->assertInstanceOf(\JPI\Database\Query\Result\PaginatedCollection::class, $result);
    }

    public function testSelectWithPaginationFalse(): void {
        $database = $this->createMock(Database::class);

        // Should call selectAll but not selectFirst (which count() uses internally)
        $database->expects($this->once())->method("selectAll");
        $database->expects($this->never())->method("selectFirst");

        // When withPagination is false, should return Collection instead of PaginatedCollection
        $result = $this->createBuilder($database)
            ->limit(2)
            ->select(false);
        $this->assertInstanceOf(\JPI\Database\Query\Result\Collection::class, $result);
        $this->assertNotInstanceOf(\JPI\Database\Query\Result\PaginatedCollection::class, $result);
    }
}

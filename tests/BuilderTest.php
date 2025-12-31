<?php

declare(strict_types=1);

namespace JPI\Database\Query\Tests;

use JPI\Database;
use JPI\Database\Query\Builder;
use JPI\Database\Query\Clause\Where;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class BuilderTest extends TestCase {

    private function createDatabase(): Database&Stub {
        $database = $this->createStub(Database::class);
        $database->method('selectAll')
            ->willReturn([
                ['id' => 1, 'name' => 'Test 1'],
                ['id' => 2, 'name' => 'Test 2'],
            ]);

        $database->method('selectFirst')
            ->willReturnCallback(function (string $query, array $params) {
                if (str_contains($query, 'as count')) {
                    return ['count' => 10];
                }
                return ['id' => 1, 'name' => 'Test 1'];
            });

        return $database;
    }

    /**
     * Helper method to access protected params property using reflection
     */
    private function getParams(Builder $builder): array {
        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty("params");
        $property->setAccessible(true);
        return $property->getValue($builder);
    }

    public function testSelectBuilding(): void {
        $database = $this->createDatabase();

        $builder = new Builder($database, "table_one");

        // Just select all
        $this->assertSame(
            "SELECT *
FROM table_one;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($this->getParams($builder));

        // Changing table
        $builder->table("table");
        $this->assertSame(
            "SELECT *
FROM table;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($this->getParams($builder));

        // Single column
        $builder->column("column");
        $this->assertSame(
            "SELECT column
FROM table;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($this->getParams($builder));

        // + another column with an alias
        $builder->column("column_two", "column_two_alias");
        $this->assertSame(
            "SELECT column,column_two as column_two_alias
FROM table;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($this->getParams($builder));

        // + single where clause
        $builder->where("column_one", "=", 1);
        $this->assertSame(
            "SELECT column,column_two as column_two_alias
FROM table
WHERE column_one = :column_one;",
            $builder->getSelectQuery()
        );
        $this->assertSame(["column_one" => 1], $this->getParams($builder));

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
            $this->getParams($builder)
        );

        // + inner OR where
        $orWhere = new Where\OrCondition($builder);
        $orWhere->where("column_three", "=", 3)
            ->where("column_four", "=", 4)
        ;
        $builder->where($orWhere);
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
            $this->getParams($builder)
        );

        // Order by
        $builder = new Builder($database, "table_one");
        $builder->orderBy("column_one");
        $this->assertSame(
            "SELECT *
FROM table_one
ORDER BY column_one ASC;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($this->getParams($builder));

        // + another order by
        $builder->orderBy("column_two", false);
        $this->assertSame(
            "SELECT *
FROM table_one
ORDER BY column_one ASC, column_two DESC;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($this->getParams($builder));

        // Limit
        $builder = new Builder($database, "table_one");
        $builder->limit(5);
        $this->assertSame(
            "SELECT *
FROM table_one
LIMIT 5;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($this->getParams($builder));

        // Limit + page
        $builder->limit(5, 2);
        $this->assertSame(
            "SELECT *
FROM table_one
LIMIT 5 OFFSET 5;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($this->getParams($builder));

        // With an inner join
        $builder = new Builder($database, "table_one");
        $builder->join("table_two", "column_one = column_two");
        $this->assertSame(
            "SELECT *
FROM table_one
INNER JOIN table_two ON column_one = column_two;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($this->getParams($builder));

        // With 2 ON conditions on an inner join
        $builder = new Builder($database, "table_one");
        $builder->join(
            $builder->newJoinClause("table_two")
                ->on("column_one = column_two")
                ->on("column_three = column_four")
        );
        $this->assertSame(
            "SELECT *
FROM table_one
INNER JOIN table_two ON column_one = column_two AND column_three = column_four;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($this->getParams($builder));

        // With a right join - using helper/alias method
        $builder = new Builder($database, "table_one");
        $builder->rightJoin("table_two", "column_one = column_two");
        $this->assertSame(
            "SELECT *
FROM table_one
RIGHT JOIN table_two ON column_one = column_two;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($this->getParams($builder));

        // With a left join - using helper/alias method
        $builder = new Builder($database, "table_one");
        $builder->leftJoin("table_two", "column_one = column_two");
        $this->assertSame(
            "SELECT *
FROM table_one
LEFT JOIN table_two ON column_one = column_two;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($this->getParams($builder));

        // 2 joins
        $builder = new Builder($database, "table_one");
        $builder->join("table_two", "column_one = column_two");
        $builder->leftJoin("table_three", "column_one = column_two");
        $this->assertSame(
            "SELECT *
FROM table_one
INNER JOIN table_two ON column_one = column_two LEFT JOIN table_three ON column_one = column_two;",
            $builder->getSelectQuery()
        );
        $this->assertEmpty($this->getParams($builder));
    }

    public function testSelectWithPagination(): void {
        // withPagination defaults to true (Default behavior)

        $builder = new Builder($this->createDatabase(), "users");
        $builder->limit(2);

        $result = $builder->select();
        $this->assertInstanceOf(\JPI\Database\Query\Result\PaginatedCollection::class, $result);
    }

    public function testSelectWithPaginationFalse(): void {
        $database = $this->createMock(Database::class);

        $builder = new Builder($database, "users");
        $builder->limit(2);

        // Should call selectAll but not selectFirst (which count() uses internally)
        $database->expects($this->once())->method('selectAll');
        $database->expects($this->never())->method('selectFirst');

        // When withPagination is false, should return Collection instead of PaginatedCollection
        $result = $builder->select(false);
        $this->assertInstanceOf(\JPI\Database\Query\Result\Collection::class, $result);
        $this->assertNotInstanceOf(\JPI\Database\Query\Result\PaginatedCollection::class, $result);
    }

    public function testSelectAll(): void {
        // Without limit always returns Collection
        $builder = new Builder($this->createDatabase(), "users");
        $result = $builder->select(false);
        $this->assertInstanceOf(\JPI\Database\Query\Result\Collection::class, $result);
        $this->assertNotInstanceOf(\JPI\Database\Query\Result\PaginatedCollection::class, $result);
    }

    public function testSelectOne(): void {
        // With limit 1 always returns single result
        $builder = new Builder( $this->createDatabase(), "users");
        $builder->limit(1);
        $result = $builder->select();
        $this->assertInstanceOf(\JPI\Database\Query\ResultInterface::class, $result);
        $this->assertNotInstanceOf(\JPI\Database\Query\Result\Collection::class, $result);
    }
}

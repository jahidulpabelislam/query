<?php

declare(strict_types=1);

namespace JPI\Database\Query\Tests;

use JPI\Database\Query\Builder;
use JPI\Database\Query\Clause\Where;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JPI\Database\Query\Clause\Where
 * @covers \JPI\Database\Query\Clause\Where\AndCondition
 * @covers \JPI\Database\Query\Clause\Where\OrCondition
 * @covers \JPI\Database\Query\DelegatedParamableTrait
 * @covers \JPI\Database\Query\ParamableTrait
 * @covers \JPI\Database\Query\WhereableTrait
 */
final class WhereClauseTest extends TestCase {

    #[AllowMockObjectsWithoutExpectations]
    public function testAnd(): void {
        // Empty
        $builder = $this->createPartialMock(Builder::class, []);
        $this->assertSame("", (string)$builder->newAndCondition());
        $this->assertEmpty($builder->getParams());

        // Basic single manual where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = $builder->newAndCondition()->where("column = 1");
        $this->assertSame("column = 1", (string)$where);
        $this->assertEmpty($builder->getParams());

        // Basic single = where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = $builder->newAndCondition()->where("column", "=", 1);
        $this->assertSame("column = :column", (string)$where);
        $this->assertSame(["column" => 1], $builder->getParams());

        // Multiple
        $builder = $this->createPartialMock(Builder::class, []);
        $where = $builder->newAndCondition()
            ->where("column", "=", 4)
            ->where("column", "IN", [5, 6]);
        $this->assertSame("(column = :column AND column IN (:column_1, :column_2))", (string)$where);
        $this->assertSame(
            [
                "column" => 4,
                "column_1" => 5,
                "column_2" => 6,
            ],
            $builder->getParams()
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testOr(): void {
        // Empty
        $builder = $this->createPartialMock(Builder::class, []);
        $this->assertSame("", (string)$builder->newOrCondition());
        $this->assertEmpty($builder->getParams());

        // Basic single manual where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = $builder->newOrCondition()->where("column = 1");
        $this->assertSame("column = 1", (string)$where);
        $this->assertEmpty($builder->getParams());

        // Basic single = where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = $builder->newOrCondition()->where("column", "=", 1);
        $this->assertSame("column = :column", (string)$where);
        $this->assertSame(["column" => 1], $builder->getParams());

        // Multiple
        $builder = $this->createPartialMock(Builder::class, []);
        $where = $builder->newOrCondition()
            ->where("column", "=", 4)
            ->where("column", "IN", [5, 6]);
        $this->assertSame("(column = :column OR column IN (:column_1, :column_2))", (string)$where);
        $this->assertSame(
            [
                "column" => 4,
                "column_1" => 5,
                "column_2" => 6,
            ],
            $builder->getParams()
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testClause(): void {
        // Empty
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $this->assertSame("", (string)$where);
        $this->assertEmpty($builder->getParams());

        // Basic single manual where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column = 1");
        $this->assertSame("WHERE column = 1", (string)$where);
        $this->assertEmpty($builder->getParams());

        // Basic single = where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column", "=", 1);
        $this->assertSame("WHERE column = :column", (string)$where);
        $this->assertSame(["column" => 1], $builder->getParams());

        // Multiple
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column", "=", 4);
        $where->where("column", "IN", [5, 6]);
        $this->assertSame("WHERE column = :column AND column IN (:column_1, :column_2)", (string)$where);
        $this->assertSame(
            [
                "column" => 4,
                "column_1" => 5,
                "column_2" => 6,
            ],
            $builder->getParams()
        );

        // Multiple + inner or
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column", "=", 7);
        $where->where(
            $builder->newOrCondition()
                ->where("column = 8")
                ->where("column = 9")
        );
        $this->assertSame("WHERE column = :column AND (column = 8 OR column = 9)", (string)$where);
        $this->assertSame(["column" => 7], $builder->getParams());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testOperators(): void {
        // Single value array should use = instead of IN
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column", "IN", [1]);
        $this->assertSame("WHERE column = :column", (string)$where);
        $this->assertSame(["column" => 1], $builder->getParams());

        // IN
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column", "IN", [1, 2]);
        $this->assertSame("WHERE column IN (:column_1, :column_2)", (string)$where);
        $this->assertSame(
            [
                "column_1" => 1,
                "column_2" => 2,
            ],
            $builder->getParams()
        );

        // NOT IN
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column", "NOT IN", [7, 8, 9]);
        $this->assertSame("WHERE column NOT IN (:column_1, :column_2, :column_3)", (string)$where);
        $this->assertSame(
            [
                "column_1" => 7,
                "column_2" => 8,
                "column_3" => 9,
            ],
            $builder->getParams()
        );

        // BETWEEN
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column_one", "BETWEEN", [100, 200]);
        $this->assertSame("WHERE column_one BETWEEN :column_one_1 AND :column_one_2", (string)$where);
        $this->assertSame(
            [
                "column_one_1" => 100,
                "column_one_2" => 200,
            ],
            $builder->getParams()
        );

        // Multiple BETWEEN
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column_one", "BETWEEN", [1, 2]);
        $where->where("column_two", "BETWEEN", [3, 4]);
        $this->assertSame("WHERE column_one BETWEEN :column_one_1 AND :column_one_2 AND column_two BETWEEN :column_two_1 AND :column_two_2", (string)$where);
        $this->assertSame(
            [
                "column_one_1" => 1,
                "column_one_2" => 2,
                "column_two_1" => 3,
                "column_two_2" => 4,
            ],
            $builder->getParams()
        );

        // IS NULL
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column_one", "IS NULL");
        $this->assertSame("WHERE column_one IS NULL", (string)$where);
        $this->assertEmpty($builder->getParams());

        // IS NOT NULL
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column_one", "IS NOT NULL");
        $this->assertSame("WHERE column_one IS NOT NULL", (string)$where);
        $this->assertEmpty($builder->getParams());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testValues(): void {
        // Test empty string value
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column", "=", "");
        $this->assertSame("WHERE column = :column", (string)$where);
        $this->assertSame(["column" => ""], $builder->getParams());

        // Test using parameter placeholder
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column", "=", ":value");
        $this->assertSame("WHERE column = :value", (string)$where);
        $this->assertEmpty($builder->getParams()); // Should be empty at this point

        $where->param("value", "test_value");
        $this->assertSame(["value" => "test_value"], $builder->getParams());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSubquery(): void {
        $database = $this->createMock(\JPI\Database::class);

        $subQuery = new Builder($database, "orders");
        $subQuery->column("customer_id");
        $subQuery->where("status", "=", "completed");

        $builder = new Builder($database, "customers");
        $where = new Where($builder);
        $where->where("id", "IN", $subQuery);

        $expected = "WHERE id IN (SELECT customer_id\nFROM orders\nWHERE status = :status)";
        $this->assertSame($expected, (string)$where);
        $this->assertSame(
            [
                "status" => "completed",
            ],
            $builder->getParams()
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMultipleSubqueries(): void {
        // Multiple subqueries in same WHERE clause
        $database = $this->createMock(\JPI\Database::class);

        $subQuery1 = new Builder($database, "premium_users");
        $subQuery1->column("user_id");

        $subQuery2 = new Builder($database, "banned_users");
        $subQuery2->column("user_id");

        $builder = new Builder($database, "users");
        $where = new Where($builder);
        $where->where("id", "IN", $subQuery1);
        $where->where("id", "NOT IN", $subQuery2);

        $expected = "WHERE id IN (SELECT user_id\nFROM premium_users) AND id NOT IN (SELECT user_id\nFROM banned_users)";
        $this->assertSame($expected, (string)$where);
        $this->assertEmpty($builder->getParams());
    }
}

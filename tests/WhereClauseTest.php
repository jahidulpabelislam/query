<?php

declare(strict_types=1);

namespace JPI\Database\Query\Tests;

use JPI\Database\Query\Builder;
use JPI\Database\Query\Clause\Where;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

final class WhereClauseTest extends TestCase {

    /**
     * Helper method to access protected params property using reflection
     */
    private function getParams(Builder $builder): array {
        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty("params");
        $property->setAccessible(true);
        return $property->getValue($builder);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testAnd(): void {
        // Empty
        $builder = $this->createPartialMock(Builder::class, []);
        $this->assertSame("", (string)$builder->newAndCondition());
        $this->assertEmpty($this->getParams($builder));

        // Basic single manual where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = $builder->newAndCondition()->where("column = 1");
        $this->assertSame("column = 1", (string)$where);
        $this->assertEmpty($this->getParams($builder));

        // Basic single = where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = $builder->newAndCondition()->where("column", "=", 1);
        $this->assertSame("column = :column", (string)$where);
        $this->assertSame(["column" => 1], $this->getParams($builder));

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
            $this->getParams($builder)
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testOr(): void {
        // Empty
        $builder = $this->createPartialMock(Builder::class, []);
        $this->assertSame("", (string)$builder->newOrCondition());
        $this->assertEmpty($this->getParams($builder));

        // Basic single manual where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = $builder->newOrCondition()->where("column = 1");
        $this->assertSame("column = 1", (string)$where);
        $this->assertEmpty($this->getParams($builder));

        // Basic single = where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = $builder->newOrCondition()->where("column", "=", 1);
        $this->assertSame("column = :column", (string)$where);
        $this->assertSame(["column" => 1], $this->getParams($builder));

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
            $this->getParams($builder)
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testClause(): void {
        // Empty
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $this->assertSame("", (string)$where);
        $this->assertEmpty($this->getParams($builder));

        // Basic single manual where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column = 1");
        $this->assertSame("WHERE column = 1", (string)$where);
        $this->assertEmpty($this->getParams($builder));

        // Basic single = where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column", "=", 1);
        $this->assertSame("WHERE column = :column", (string)$where);
        $this->assertSame(["column" => 1], $this->getParams($builder));

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
            $this->getParams($builder)
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
        $this->assertSame(["column" => 7], $this->getParams($builder));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testOperators(): void {
        // Single value array should use = instead of IN
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column", "IN", [1]);
        $this->assertSame("WHERE column = :column", (string)$where);
        $this->assertSame(["column" => 1], $this->getParams($builder));

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
            $this->getParams($builder)
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
            $this->getParams($builder)
        );

        // BETWEEN
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column_one", "BETWEEN", [100, 200]);
        $this->assertSame("WHERE column_one BETWEEN :column_one_1 AND :column_one_2", (string)$where);
        $this->assertSame([
            "column_one_1" => 100,
            "column_one_2" => 200,
        ], $this->getParams($builder));

        // Multiple BETWEEN
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column_one", "BETWEEN", [1, 2]);
        $where->where("column_two", "BETWEEN", [3, 4]);
        $this->assertSame("WHERE column_one BETWEEN :column_one_1 AND :column_one_2 AND column_two BETWEEN :column_two_1 AND :column_two_2", (string)$where);
        $this->assertSame([
            "column_one_1" => 1,
            "column_one_2" => 2,
            "column_two_1" => 3,
            "column_two_2" => 4,
        ], $this->getParams($builder));
    }
}

<?php

namespace JPI\Database\Query\Tests;

use JPI\Database\Query\Builder;
use JPI\Database\Query\Clause\Where;
use PHPUnit\Framework\TestCase;

final class WhereClauseTest extends TestCase {

    public function testAnd(): void {
        $builder = $this->createPartialMock(Builder::class, []);

        // Empty
        $where = new Where\AndCondition($builder);
        $this->assertSame("", (string)$where);

        // Basic single manual where
        $where = new Where\AndCondition($builder);
        $where->where("column = 1");
        $this->assertSame("column = 1", (string)$where);

        // Basic single = where
        $where = new Where\AndCondition($builder);
        $where->where("column", "=", 1);
        $this->assertSame("column = :column", (string)$where);

        // IN
        $where = new Where\AndCondition($builder);
        $where->where("column", "IN", [2, 3]);
        $this->assertSame("column IN (:column_1, :column_2)", (string)$where);

        // Multiple
        $where = new Where\AndCondition($builder);
        $where->where("column", "=", 4);
        $where->where("column", "IN", [5, 6]);
        $this->assertSame("(column = :column AND column IN (:column_1, :column_2))", (string)$where);
    }

    public function testOr(): void {
        $builder = $this->createPartialMock(Builder::class, []);

        // Empty
        $where = new Where\OrCondition($builder);
        $this->assertSame("", (string)$where);

        // Basic single manual where
        $where = new Where\OrCondition($builder);
        $where->where("column = 1");
        $this->assertSame("column = 1", (string)$where);

        // Basic single = where
        $where = new Where\OrCondition($builder);
        $where->where("column", "=", 1);
        $this->assertSame("column = :column", (string)$where);

        // IN
        $where = new Where\OrCondition($builder);
        $where->where("column", "IN", [2, 3]);
        $this->assertSame("column IN (:column_1, :column_2)", (string)$where);

        // Multiple
        $where = new Where\OrCondition($builder);
        $where->where("column", "=", 4);
        $where->where("column", "IN", [5, 6]);
        $this->assertSame("(column = :column OR column IN (:column_1, :column_2))", (string)$where);
    }

    public function testClause(): void {
        $builder = $this->createPartialMock(Builder::class, []);

        // Empty
        $where = new Where($builder);
        $this->assertSame("", (string)$where);

        // Basic single manual where
        $where = new Where($builder);
        $where->where("column = 1");
        $this->assertSame("WHERE column = 1", (string)$where);

        // Basic single = where
        $where = new Where($builder);
        $where->where("column", "=", 1);
        $this->assertSame("WHERE column = :column", (string)$where);

        // IN
        $where = new Where($builder);
        $where->where("column", "IN", [1, 2]);
        $this->assertSame("WHERE column IN (:column_1, :column_2)", (string)$where);

        // Multiple
        $where = new Where($builder);
        $where->where("column", "=", 4);
        $where->where("column", "IN", [5, 6]);
        $this->assertSame("WHERE column = :column AND column IN (:column_1, :column_2)", (string)$where);

        $where = new Where($builder);
        $where->where("column", "=", 7);
        $where->where(
            (new Where\OrCondition($builder))
                ->where("column = 8")
                ->where("column = 9")
        );
        $this->assertSame("WHERE column = :column AND (column = 8 OR column = 9)", (string)$where);
    }

    public function testMultipleDifferentValuesForSameColumn(): void {
        $builder = $this->createPartialMock(Builder::class, []);

        // Test the issue: same column with different operators
        $where = new Where($builder);
        $where->where("id", ">=", 25);
        $where->where("id", "<=", 35);
        
        // Should generate unique parameter names
        $this->assertSame("WHERE id >= :id AND id <= :id_1", (string)$where);
        
        // Verify both parameters are stored
        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('params');
        $property->setAccessible(true);
        $params = $property->getValue($builder);
        
        $this->assertArrayHasKey('id', $params);
        $this->assertSame(25, $params['id']);
        $this->assertArrayHasKey('id_1', $params);
        $this->assertSame(35, $params['id_1']);
        
        // Test edge case: IN followed by single value comparison
        $builder2 = $this->createPartialMock(Builder::class, []);
        $where2 = new Where($builder2);
        $where2->where("status", "IN", ["active", "pending"]);
        $where2->where("status", "!=", "deleted");
        
        $this->assertSame("WHERE status IN (:status_1, :status_2) AND status != :status_3", (string)$where2);
        
        $reflection2 = new \ReflectionClass($builder2);
        $property2 = $reflection2->getProperty('params');
        $property2->setAccessible(true);
        $params2 = $property2->getValue($builder2);
        
        $this->assertArrayHasKey('status_1', $params2);
        $this->assertSame('active', $params2['status_1']);
        $this->assertArrayHasKey('status_2', $params2);
        $this->assertSame('pending', $params2['status_2']);
        $this->assertArrayHasKey('status_3', $params2);
        $this->assertSame('deleted', $params2['status_3']);
    }
}

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
        $where->where('column = 1');
        $this->assertSame("column = 1", (string)$where);

        // Basic single = where
        $where = new Where\AndCondition($builder);
        $where->where('column', '=', 1);
        $this->assertSame("column = :column", (string)$where);

        // IN
        $where = new Where\AndCondition($builder);
        $where->where('column', 'IN', [2, 3]);
        $this->assertSame("column IN (:column_1, :column_2)", (string)$where);

        // Multiple
        $where = new Where\AndCondition($builder);
        $where->where('column', '=', 4);
        $where->where('column', 'IN', [5, 6]);
        $this->assertSame("(column = :column AND column IN (:column_1, :column_2))", (string)$where);
    }

    public function testOr(): void {
        $builder = $this->createPartialMock(Builder::class, []);

        // Empty
        $where = new Where\OrCondition($builder);
        $this->assertSame("", (string)$where);

        // Basic single manual where
        $where = new Where\OrCondition($builder);
        $where->where('column = 1');
        $this->assertSame("column = 1", (string)$where);

        // Basic single = where
        $where = new Where\OrCondition($builder);
        $where->where('column', '=', 1);
        $this->assertSame("column = :column", (string)$where);

        // IN
        $where = new Where\OrCondition($builder);
        $where->where('column', 'IN', [2, 3]);
        $this->assertSame("column IN (:column_1, :column_2)", (string)$where);

        // Multiple
        $where = new Where\OrCondition($builder);
        $where->where('column', '=', 4);
        $where->where('column', 'IN', [5, 6]);
        $this->assertSame("(column = :column OR column IN (:column_1, :column_2))", (string)$where);
    }

    public function testClause(): void {
        $builder = $this->createPartialMock(Builder::class, []);

        // Empty
        $where = new Where($builder);
        $this->assertSame("", (string)$where);

        // Basic single manual where
        $where = new Where($builder);
        $where->where('column = 1');
        $this->assertSame("WHERE column = 1", (string)$where);

        // Basic single = where
        $where = new Where($builder);
        $where->where('column', '=', 1);
        $this->assertSame("WHERE column = :column", (string)$where);

        // IN
        $where = new Where($builder);
        $where->where('column', 'IN', [1, 2]);
        $this->assertSame("WHERE column IN (:column_1, :column_2)", (string)$where);

        // Multiple
        $where = new Where($builder);
        $where->where('column', '=', 4);
        $where->where('column', 'IN', [5, 6]);
        $this->assertSame("WHERE column = :column AND column IN (:column_1, :column_2)", (string)$where);

        $where = new Where($builder);
        $where->where('column', '=', 7);
        $where->where(
            (new Where\OrCondition($builder))
                ->where('column = 8')
                ->where('column = 9')
        );
        $this->assertSame("WHERE column = :column AND (column = 8 OR column = 9)", (string)$where);
    }
}

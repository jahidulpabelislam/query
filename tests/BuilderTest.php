<?php

namespace JPI\Database\Query\Tests;

use JPI\Database;
use JPI\Database\Query\Builder;
use JPI\Database\Query\Clause\Where;
use PHPUnit\Framework\TestCase;

final class BuilderTest extends TestCase {

    public function testAll(): void {
        $database = $this->createMock(Database::class);

        $builder = new Builder($database, 'table_one');

        // Just select all
        $this->assertSame(
            "SELECT *
FROM table_one;",
            $builder->getSelectQuery()
        );

        // Changing table
        $builder->table('table');
        $this->assertSame(
            "SELECT *
FROM table;",
            $builder->getSelectQuery()
        );

        // Single column
        $builder->column('column');
        $this->assertSame(
            "SELECT column
FROM table;",
            $builder->getSelectQuery()
        );

        // + another column with an alias
        $builder->column('column_two', 'column_two_alias');
        $this->assertSame(
            "SELECT column,column_two as column_two_alias
FROM table;",
            $builder->getSelectQuery()
        );

        // + single where clause
        $builder->where('column_one', '=', 1);
        $this->assertSame(
            "SELECT column,column_two as column_two_alias
FROM table
WHERE column_one = :column_one;",
            $builder->getSelectQuery()
        );

        // + another where clause
        $builder->where('column_two', '=', 2);
        $this->assertSame(
            "SELECT column,column_two as column_two_alias
FROM table
WHERE column_one = :column_one AND column_two = :column_two;",
            $builder->getSelectQuery()
        );

        // + inner OR where
        $orWhere = new Where\OrCondition($builder);
        $orWhere->where('column_three', '=', 3)
            ->where('column_four', '=', 4)
        ;
        $builder->where((string)$orWhere);
        $this->assertSame(
            "SELECT column,column_two as column_two_alias
FROM table
WHERE column_one = :column_one AND column_two = :column_two AND (column_three = :column_three OR column_four = :column_four);",
            $builder->getSelectQuery()
        );

        // Order by
        $builder = new Builder($database, 'table_one');
        $builder->orderBy('column_one');
        $this->assertSame(
            "SELECT *
FROM table_one
ORDER BY column_one ASC;",
            $builder->getSelectQuery()
        );

        // + another order by
        $builder->orderBy('column_two', false);
        $this->assertSame(
            "SELECT *
FROM table_one
ORDER BY column_one ASC, column_two DESC;",
            $builder->getSelectQuery()
        );

        // Limit
        $builder = new Builder($database, 'table_one');
        $builder->limit(5);
        $this->assertSame(
            "SELECT *
FROM table_one
LIMIT 5;",
            $builder->getSelectQuery()
        );

        // Limit + page
        $builder->limit(5,  2);
        $this->assertSame(
            "SELECT *
FROM table_one
LIMIT 5 OFFSET 5;",
            $builder->getSelectQuery()
        );
    }
}

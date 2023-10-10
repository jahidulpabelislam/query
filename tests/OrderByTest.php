<?php

namespace JPI\Database\Query\Tests;

use JPI\Database\Query\Builder;
use JPI\Database\Query\Clause\OrderBy;
use PHPUnit\Framework\TestCase;

final class OrderByTest extends TestCase {

    public function testAll(): void {
        $builder = $this->createPartialMock(Builder::class, []);

        // Empty
        $orderBy = new OrderBy($builder);
        $this->assertSame("", (string)$orderBy);

        // Basic single clause
        $orderBy = new OrderBy($builder);
        $orderBy[] = "column_one";
        $this->assertSame("ORDER BY column_one", (string)$orderBy);

        // Multiple
        $orderBy = new OrderBy($builder);
        $orderBy[] = "column_one";
        $orderBy[] = "column_two";
        $this->assertSame("ORDER BY column_one, column_two", (string)$orderBy);

        // Cloning
        $orderBy1 = new OrderBy($builder);
        $orderBy1[] = "column_one";

        $orderBy2 = clone $orderBy1;
        $orderBy2[] = "column_two";

        $this->assertSame("ORDER BY column_one", (string)$orderBy1); // Shouldn't have changed
        $this->assertSame("ORDER BY column_one, column_two", (string)$orderBy2); // Should get the new one
    }
}

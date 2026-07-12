<?php

declare(strict_types=1);

namespace JPI\Database\Query\Tests\Unit;

use JPI\Database\Query\Builder;
use JPI\Database\Query\Clause\OrderBy;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JPI\Database\Query\Clause\OrderBy
 */
final class OrderByTest extends TestCase {

    #[AllowMockObjectsWithoutExpectations]
    public function testAll(): void {
        $builder = $this->createPartialMock(Builder::class, []);

        // Empty
        $orderBy = new OrderBy($builder);
        $this->assertSame("", (string)$orderBy);

        // Basic single clause
        $orderBy = new OrderBy($builder);
        $orderBy[] = "created_at";
        $this->assertSame("ORDER BY created_at", (string)$orderBy);

        // Multiple
        $orderBy = new OrderBy($builder);
        $orderBy[] = "last_name";
        $orderBy[] = "first_name";
        $this->assertSame("ORDER BY last_name, first_name", (string)$orderBy);

        // Cloning
        $orderBy1 = new OrderBy($builder);
        $orderBy1[] = "email";

        $orderBy2 = clone $orderBy1;
        $orderBy2[] = "created_at";

        $this->assertSame("ORDER BY email", (string)$orderBy1); // Shouldn't have changed
        $this->assertSame("ORDER BY email, created_at", (string)$orderBy2); // Should get the new one
    }
}

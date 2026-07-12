<?php

declare(strict_types=1);

namespace JPI\Database\Query\Tests\Unit;

use JPI\Database;
use JPI\Database\Query\Builder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase {

    protected function createDatabase(): (Database&MockObject)|(Database&Stub) {
        return $this->createMock(Database::class);
    }

    protected function createBuilder(?Database $database = null): Builder {
        return new Builder($database ?: $this->createDatabase(), "users");
    }
}

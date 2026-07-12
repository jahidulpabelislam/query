<?php

declare(strict_types=1);

namespace JPI\Database\Query\Tests\Unit;

/**
 * Check the SQL generated
 *
 * @covers \JPI\Database\Query\Builder::count
 * @covers \JPI\Database\Query\Clause\Where
 * @covers \JPI\Database\Query\Clause\Where\AndCondition
 * @covers \JPI\Database\Query\DelegatedParamableTrait
 * @covers \JPI\Database\Query\ParamableTrait
 * @covers \JPI\Database\Query\WhereableTrait
 */
final class CountTest extends BaseTestCase {

    public function testBasic(): void {
        $database = $this->createDatabase();

        $database->expects($this->once())
            ->method("selectFirst")
            ->with(
                $this->equalTo("SELECT COUNT(*) as count
FROM users
LIMIT 1;"),
                $this->equalTo([])
            )
            ->willReturn(["count" => 1])
        ;

        $this->createBuilder($database)->count();
    }

    public function testWithColumn(): void {
        $database = $this->createDatabase();

        $database->expects($this->once())
            ->method("selectFirst")
            ->with(
                $this->equalTo("SELECT COUNT(id) as count
FROM users
LIMIT 1;"),
                $this->equalTo([])
            )
            ->willReturn(["count" => 1])
        ;

        $this->createBuilder($database)->count("id");
    }

    public function testWithWhere(): void {
        $database = $this->createDatabase();

        $database->expects($this->once())
            ->method("selectFirst")
            ->with(
                $this->equalTo("SELECT COUNT(*) as count
FROM users
WHERE status = :status
LIMIT 1;"),
                $this->equalTo([
                    "status" => "active",
                ])
            )
            ->willReturn(["count" => 1])
        ;

        $this->createBuilder($database)
            ->where("status", "=", "active")
            ->count();
    }
}

<?php

declare(strict_types=1);

namespace JPI\Database\Query\Tests\Unit;

/**
 * Check the SQL generated.
 *
 * @covers \JPI\Database\Query\Builder::delete
 * @covers \JPI\Database\Query\Clause\Where
 * @covers \JPI\Database\Query\Clause\Where\AndCondition
 * @covers \JPI\Database\Query\ParamableTrait
 * @covers \JPI\Database\Query\WhereableTrait
 */
final class DeleteTest extends BaseTestCase {

    public function testBasic(): void {
        $database = $this->createDatabase();

        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("DELETE FROM users;"),
                $this->equalTo([])
            )
            ->willReturn(10)
        ;

        $this->createBuilder($database)->delete();
    }

    public function testWithWhere(): void {
        $database = $this->createDatabase();

        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("DELETE FROM users
WHERE id = :id;"),
                $this->equalTo([
                    "id" => 123,
                ])
            )
            ->willReturn(1)
        ;

        $this->createBuilder($database)
            ->where("id", "=", 123)
            ->delete();
    }

    public function testWithLimit(): void {
        $database = $this->createDatabase();

        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("DELETE FROM users
LIMIT 10;"),
                $this->equalTo([])
            )
            ->willReturn(10)
        ;

        $this->createBuilder($database)
            ->limit(10)
            ->delete();
    }

    public function testWithOrderBy(): void {
        $database = $this->createDatabase();

        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("DELETE FROM users
ORDER BY created_at ASC;"),
                $this->equalTo([])
            )
            ->willReturn(3)
        ;

        $this->createBuilder($database)
            ->orderBy("created_at", true)
            ->delete();
    }

    public function testWithWhereOrderByAndLimit(): void {
        $database = $this->createDatabase();

        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("DELETE FROM users
WHERE status = :status
ORDER BY created_at DESC
LIMIT 5;"),
                $this->equalTo([
                    "status" => "active",
                ])
            )
            ->willReturn(5)
        ;

        $this->createBuilder($database)
            ->where("status", "=", "active")
            ->orderBy("created_at", false)
            ->limit(5)
            ->delete();
    }
}

<?php

declare(strict_types=1);

namespace JPI\Database\Query\Tests\Unit;

/**
 * Check the SQL generated
 *
 * @covers \JPI\Database\Query\Builder::update
 * @covers \JPI\Database\Query\Clause\Where
 * @covers \JPI\Database\Query\Clause\Where\AndCondition
 * @covers \JPI\Database\Query\ParamableTrait
 * @covers \JPI\Database\Query\WhereableTrait
 */
final class UpdateTest extends BaseTestCase {

    public function testBasic(): void {
        $database = $this->createDatabase();

        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("UPDATE users
SET name = :name,email = :email,age = :age;"),
                $this->equalTo([
                    "name" => "John Doe",
                    "email" => "john@example.com",
                    "age" => null,
                ])
            )
            ->willReturn(1)
        ;

        $this->createBuilder($database)->update([
            "name" => "John Doe",
            "email" => "john@example.com",
            "age" => null,
        ]);
    }

    public function testWithWhere(): void {
        $database = $this->createDatabase();

        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("UPDATE users
SET name = :name
WHERE id = :id;"),
                $this->equalTo([
                    "name" => "Jane Doe",
                    "id" => 123,
                ])
            )
            ->willReturn(1)
        ;

        $this->createBuilder($database)
            ->where("id", "=", 123)
            ->update([
                "name" => "Jane Doe",
            ]);
    }

    public function testWithOrderBy(): void {
        $database = $this->createDatabase();

        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("UPDATE users
SET score = :score
ORDER BY created_at DESC;"),
                $this->equalTo([
                    "score" => 100,
                ])
            )
            ->willReturn(5)
        ;

        $this->createBuilder($database)
            ->orderBy("created_at", false)
            ->update([
                "score" => 100,
            ]);
    }

    public function testWithLimit(): void {
        $database = $this->createDatabase();

        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("UPDATE users
SET status = :status
LIMIT 10;"),
                $this->equalTo([
                    "status" => "active",
                ])
            )
            ->willReturn(10)
        ;

        $this->createBuilder($database)
            ->limit(10)
            ->update([
                "status" => "active",
            ]);
    }

    public function testWithWhereOrderByAndLimit(): void {
        $database = $this->createDatabase();

        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("UPDATE users
SET status = :status
WHERE status_now = :status_now
ORDER BY created_at ASC
LIMIT 5;"),
                $this->equalTo([
                    "status_now" => "active",
                    "status" => "pending",
                ])
            )
            ->willReturn(5)
        ;

        $this->createBuilder($database)
            ->where("status_now", "=", "active")
            ->orderBy("created_at", true)
            ->limit(5)
            ->update([
                "status" => "pending",
            ]);
    }
}

<?php

declare(strict_types=1);

namespace JPI\Database\Query\Tests;

use JPI\Database;

final class DeleteTest extends BaseTestCase {

    public function testBasicDelete(): void {
        $database = $this->createDatabaseMock();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("DELETE FROM users;"),
                $this->equalTo([])
            )
            ->willReturn(10)
        ;

        $result = $this->createBuilder($database)->delete();

        // Confirm row count is returned
        $this->assertSame(10, $result);
    }

    public function testDeleteWithWhere(): void {
        $database = $this->createDatabaseMock();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("DELETE FROM users\nWHERE id = :id;"),
                $this->equalTo([
                    "id" => 123,
                ])
            )
            ->willReturn(1)
        ;

        $result = $this->createBuilder($database)
            ->where("id", "=", 123)
            ->delete();

        $this->assertSame(1, $result);
    }

    public function testDeleteWithMultipleWhereConditions(): void {
        $database = $this->createDatabaseMock();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("DELETE FROM users\nWHERE active = :active AND status = :status;"),
                $this->equalTo([
                    "active" => 0,
                    "status" => "deleted",
                ])
            )
            ->willReturn(5)
        ;

        $result = $this->createBuilder($database)
            ->where("active", "=", 0)
            ->where("status", "=", "deleted")
            ->delete();

        $this->assertSame(5, $result);
    }

    public function testDeleteWithOrderBy(): void {
        $database = $this->createDatabaseMock();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("DELETE FROM users\nORDER BY created_at ASC;"),
                $this->equalTo([])
            )
            ->willReturn(3)
        ;

        $result = $this->createBuilder($database)
            ->orderBy("created_at", true)
            ->delete();

        $this->assertSame(3, $result);
    }

    public function testDeleteWithLimit(): void {
        $database = $this->createDatabaseMock();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("DELETE FROM users\nLIMIT 10;"),
                $this->equalTo([])
            )
            ->willReturn(10)
        ;

        $result = $this->createBuilder($database)
            ->limit(10)
            ->delete();

        $this->assertSame(10, $result);
    }

    public function testDeleteWithWhereOrderByAndLimit(): void {
        $database = $this->createDatabaseMock();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("DELETE FROM users\nWHERE active = :active\nORDER BY created_at DESC\nLIMIT 5;"),
                $this->equalTo([
                    "active" => 0,
                ])
            )
            ->willReturn(5)
        ;

        $result = $this->createBuilder($database)
            ->where("active", "=", 0)
            ->orderBy("created_at", false)
            ->limit(5)
            ->delete();

        $this->assertSame(5, $result);
    }

    public function testDeleteNoRowsAffected(): void {
        $database = $this->createDatabaseMock();

        // Simulate no rows affected
        $database->expects($this->once())
            ->method("exec")
            ->willReturn(0);

        $result = $this->createBuilder($database)
            ->where("id", "=", 999)
            ->delete();

        $this->assertSame(0, $result);
    }

    public function testDeleteWithComplexWhereClause(): void {
        $database = $this->createDatabaseMock();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("DELETE FROM users\nWHERE status = :status AND (role = :role OR age = :age);"),
                $this->equalTo([
                    "status" => "inactive",
                    "role" => "guest",
                    "age" => 18,
                ])
            )
            ->willReturn(7)
        ;

        $builder = $this->createBuilder($database);
        $result = $builder
            ->where("status", "=", "inactive")
            ->where(
                $builder->newOrCondition()
                    ->where("role", "=", "guest")
                    ->where("age", "=", 18)
            )
            ->delete();

        $this->assertSame(7, $result);
    }
}

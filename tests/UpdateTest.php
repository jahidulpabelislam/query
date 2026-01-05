<?php

declare(strict_types=1);

namespace JPI\Database\Query\Tests;

use JPI\Database;

final class UpdateTest extends BaseTestCase {

    public function testBasicUpdate(): void {
        $database = $this->createDatabaseMock();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("UPDATE users\nSET name = :name,email = :email;"),
                $this->equalTo([
                    "name" => "John Doe",
                    "email" => "john@example.com",
                ])
            )
            ->willReturn(1)
        ;

        $result = $this->createBuilder($database)->update([
            "name" => "John Doe",
            "email" => "john@example.com",
        ]);

        // Confirm row count is returned
        $this->assertSame(1, $result);
    }

    public function testUpdateWithWhere(): void {
        $database = $this->createDatabaseMock();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("UPDATE users\nSET name = :name\nWHERE id = :id;"),
                $this->equalTo([
                    "name" => "Jane Doe",
                    "id" => 123,
                ])
            )
            ->willReturn(1)
        ;

        $result = $this->createBuilder($database)
            ->where("id", "=", 123)
            ->update([
                "name" => "Jane Doe",
            ]);

        $this->assertSame(1, $result);
    }

    public function testUpdateWithOrderBy(): void {
        $database = $this->createDatabaseMock();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("UPDATE users\nSET score = :score\nORDER BY created_at DESC;"),
                $this->equalTo([
                    "score" => 100,
                ])
            )
            ->willReturn(5)
        ;

        $result = $this->createBuilder($database)
            ->orderBy("created_at", false)
            ->update([
                "score" => 100,
            ]);

        $this->assertSame(5, $result);
    }

    public function testUpdateWithLimit(): void {
        $database = $this->createDatabaseMock();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("UPDATE users\nSET active = :active\nLIMIT 10;"),
                $this->equalTo([
                    "active" => 0,
                ])
            )
            ->willReturn(10)
        ;

        $result = $this->createBuilder($database)
            ->limit(10)
            ->update([
                "active" => 0,
            ]);

        $this->assertSame(10, $result);
    }

    public function testUpdateWithWhereOrderByAndLimit(): void {
        $database = $this->createDatabaseMock();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("UPDATE users\nSET status = :status\nWHERE active = :active\nORDER BY created_at ASC\nLIMIT 5;"),
                $this->equalTo([
                    "status" => "pending",
                    "active" => 1,
                ])
            )
            ->willReturn(5)
        ;

        $result = $this->createBuilder($database)
            ->where("active", "=", 1)
            ->orderBy("created_at", true)
            ->limit(5)
            ->update([
                "status" => "pending",
            ]);

        $this->assertSame(5, $result);
    }

    public function testUpdateMultipleColumns(): void {
        $database = $this->createDatabaseMock();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("UPDATE users\nSET name = :name,email = :email,age = :age,status = :status;"),
                $this->equalTo([
                    "name" => "John Doe",
                    "email" => "john@example.com",
                    "age" => 30,
                    "status" => "active",
                ])
            )
            ->willReturn(1)
        ;

        $result = $this->createBuilder($database)->update([
            "name" => "John Doe",
            "email" => "john@example.com",
            "age" => 30,
            "status" => "active",
        ]);

        $this->assertSame(1, $result);
    }
}

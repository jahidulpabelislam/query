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

        $this->createBuilder($database)->delete();
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

        $this->createBuilder($database)
            ->where("id", "=", 123)
            ->delete();
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

        $this->createBuilder($database)
            ->orderBy("created_at", true)
            ->delete();
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

        $this->createBuilder($database)
            ->limit(10)
            ->delete();
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

        $this->createBuilder($database)
            ->where("active", "=", 0)
            ->orderBy("created_at", false)
            ->limit(5)
            ->delete();
    }
}

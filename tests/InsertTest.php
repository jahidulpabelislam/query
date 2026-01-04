<?php

declare(strict_types=1);

namespace JPI\Database\Query\Tests;

use InvalidArgumentException;
use JPI\Database;
use JPI\Database\Query\Builder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class InsertTest extends TestCase {

    private function createDatabaseMock(): Database&MockObject {
        return $this->createMock(Database::class);
    }

    private function createBuilder(?Database $database = null): Builder {
        return new Builder($database ?: $this->createDatabaseMock(), "users");
    }

    public function testLegacySingleRow(): void {
        $database = $this->createDatabaseMock();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("INSERT INTO users\n(name,email)\nVALUES (:name__row1,:email__row1);"),
                $this->equalTo([
                    "name__row1" => "John Doe",
                    "email__row1" => "john@example.com",
                ])
            )
            ->willReturn(1)
        ;

        // Confirm getLastInsertedId is called - number isn't important
        $database->expects($this->once())->method("getLastInsertedId")->willReturn(123);

        $result = $this->createBuilder($database)->insert([
            "name" => "John Doe",
            "email" => "john@example.com",
        ]);

        // Confirm the last inserted ID is returned
        $this->assertSame(123, $result);
    }

    public function testSingleRow(): void {
        $database = $this->createDatabaseMock();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("INSERT INTO users\n(name,email)\nVALUES (:name__row1,:email__row1);"),
                $this->equalTo([
                    "name__row1" => "John Doe",
                    "email__row1" => "john@example.com",
                ])
            )
            ->willReturn(1)
        ;

        // Confirm getLastInsertedId is called - number isn't important
        $database->expects($this->once())->method("getLastInsertedId")->willReturn(123);

        $result = $this->createBuilder($database)->insert([[
            "name" => "John Doe",
            "email" => "john@example.com",
        ]]);

        // Confirm the last inserted ID is returned
        $this->assertSame(123, $result);
    }

    public function testMultiRow(): void {
        $database = $this->createDatabaseMock();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("INSERT INTO users\n(name,email)\nVALUES (:name__row1,:email__row1),(:name__row2,:email__row2);"),
                $this->equalTo([
                    "name__row1" => "John Doe",
                    "email__row1" => "john@example.com",
                    "name__row2" => "Jane Doe",
                    "email__row2" => "jane@example.com",
                ])
            )
            ->willReturn(2)
        ;

        // Confirm getLastInsertedId isn't called
        $database->expects($this->never())->method("getLastInsertedId");

        $builder = $this->createBuilder($database);
        $result = $builder->insert([
            ["name" => "John Doe", "email" => "john@example.com"],
            ["name" => "Jane Doe", "email" => "jane@example.com"],
        ]);

        // Confirm null is returned for multi-row insert
        $this->assertNull($result);
    }

    public function testFailure(): void {
        $database = $this->createDatabaseMock();

        // Simulate failed insert
        $database->method("exec")->willReturn(0);

        $database->expects($this->never())->method("getLastInsertedId");

        $result = $this->createBuilder($database)->insert([
            "name" => "Test User",
            "email" => "test@example.com",
        ]);

        $this->assertNull($result);
    }

    public function testEmptyRecord(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Record(s) passed to insert() cannot be empty.");

        $database = $this->createStub(Database::class);
        $builder = $this->createBuilder($database);
        $builder->insert([]);
    }

    public function testMismatchedColumns(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("All records passed to insert() must have the same set of columns.");

        $database = $this->createStub(Database::class);
        $builder = $this->createBuilder($database);
        $builder->insert([
            ["name" => "John Doe", "email" => "john@example.com"],
            ["name" => "Jane Doe", "age" => 30],
        ]);
    }
}

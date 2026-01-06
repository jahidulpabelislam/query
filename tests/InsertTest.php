<?php

declare(strict_types=1);

namespace JPI\Database\Query\Tests;

use InvalidArgumentException;
use JPI\Database;

/**
 * Check the SQL generated
 *
 * @covers \JPI\Database\Query\Builder::insert
 * @covers \JPI\Database\Query\ParamableTrait
 * @covers \JPI\Database\Query\WhereableTrait
 */
final class InsertTest extends BaseTestCase {

    public function testLegacySingleRow(): void {
        $database = $this->createDatabase();

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
        $database = $this->createDatabase();

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
        $database = $this->createDatabase();

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

        // Confirm row count is returned for multi-row insert
        $this->assertSame(2, $result);
    }

    public function testMultiRowDifferentOrder(): void {
        $database = $this->createDatabase();

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
            ["email" => "jane@example.com", "name" => "Jane Doe"],
        ]);

        // Confirm row count is returned for multi-row insert
        $this->assertSame(2, $result);
    }

    public function testFailure(): void {
        $database = $this->createDatabase();

        // Simulate failed insert
        $database->method("exec")->willReturn(0);

        $database->expects($this->never())->method("getLastInsertedId");

        $result = $this->createBuilder($database)->insert([
            "name" => "Test User",
            "email" => "test@example.com",
        ]);

        $this->assertNull($result);
    }

    public function testMultiRowFailure(): void {
        $database = $this->createDatabase();

        // Simulate failed multi-row insert
        $database->method("exec")->willReturn(0);

        $database->expects($this->never())->method("getLastInsertedId");

        $result = $this->createBuilder($database)->insert([
            ["name" => "John Doe", "email" => "john@example.com"],
            ["name" => "Jane Doe", "email" => "jane@example.com"],
        ]);

        // Confirm 0 is returned for failed multi-row insert
        $this->assertSame(0, $result);
    }

    public function testEmptyRecords(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Record(s) passed to insert() cannot be empty.");

        $database = $this->createStub(Database::class);
        $builder = $this->createBuilder($database);
        $builder->insert([]);
    }

    public function testEmptyInnerRecord(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Record(s) passed to insert() cannot be empty.");

        $database = $this->createStub(Database::class);
        $builder = $this->createBuilder($database);
        $builder->insert([[]]);
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

    public function testMissingSecondRecord(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("All records passed to insert() must have the same set of columns.");

        $database = $this->createStub(Database::class);
        $builder = $this->createBuilder($database);
        $builder->insert([
            ["name" => "John Doe", "email" => "john@example.com"],
            [],
        ]);
    }
}

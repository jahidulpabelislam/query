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
        return new Builder($database ?: $this->createDatabaseMock(), "test_table");
    }

    public function testSingleRowInsertGeneratesCorrectSQL(): void {
        $database = $this->createDatabaseMock();
        
        // Capture the SQL query passed to exec
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("INSERT INTO test_table\n(name,email)\nVALUES (:name__row1,:email__row1);"),
                $this->equalTo([
                    "name__row1" => "John Doe",
                    "email__row1" => "john@example.com",
                ])
            )
            ->willReturn(1);
        
        $database->expects($this->once())
            ->method("getLastInsertedId")
            ->willReturn(123);
        
        $builder = $this->createBuilder($database);
        $result = $builder->insert([
            "name" => "John Doe",
            "email" => "john@example.com",
        ]);
        
        $this->assertSame(123, $result);
    }

    public function testSingleRowInsertReturnsLastInsertedId(): void {
        $database = $this->createDatabaseMock();
        
        $database->method("exec")
            ->willReturn(1);
        
        $database->expects($this->once())
            ->method("getLastInsertedId")
            ->willReturn(456);
        
        $builder = $this->createBuilder($database);
        $result = $builder->insert([
            "name" => "Jane Doe",
            "email" => "jane@example.com",
        ]);
        
        $this->assertSame(456, $result);
    }

    public function testMultiRowInsertGeneratesCorrectSQL(): void {
        $database = $this->createDatabaseMock();
        
        // Capture the SQL query passed to exec
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("INSERT INTO test_table\n(name,email)\nVALUES (:name__row1,:email__row1),(:name__row2,:email__row2);"),
                $this->equalTo([
                    "name__row1" => "John Doe",
                    "email__row1" => "john@example.com",
                    "name__row2" => "Jane Doe",
                    "email__row2" => "jane@example.com",
                ])
            )
            ->willReturn(2);
        
        $database->expects($this->never())
            ->method("getLastInsertedId");
        
        $builder = $this->createBuilder($database);
        $result = $builder->insert([
            ["name" => "John Doe", "email" => "john@example.com"],
            ["name" => "Jane Doe", "email" => "jane@example.com"],
        ]);
        
        $this->assertNull($result);
    }

    public function testMultiRowInsertReturnsNull(): void {
        $database = $this->createDatabaseMock();
        
        $database->method("exec")
            ->willReturn(3);
        
        $database->expects($this->never())
            ->method("getLastInsertedId");
        
        $builder = $this->createBuilder($database);
        $result = $builder->insert([
            ["name" => "User 1", "email" => "user1@example.com"],
            ["name" => "User 2", "email" => "user2@example.com"],
            ["name" => "User 3", "email" => "user3@example.com"],
        ]);
        
        $this->assertNull($result);
    }

    public function testInsertWithNoRowsAffectedReturnsNull(): void {
        $database = $this->createDatabaseMock();
        
        $database->method("exec")
            ->willReturn(0);
        
        $database->expects($this->never())
            ->method("getLastInsertedId");
        
        $builder = $this->createBuilder($database);
        $result = $builder->insert([
            "name" => "Test User",
            "email" => "test@example.com",
        ]);
        
        $this->assertNull($result);
    }

    public function testInsertThrowsExceptionForEmptyRecord(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Record(s) passed to insert() cannot be empty.");
        
        $database = $this->createStub(Database::class);
        $builder = $this->createBuilder($database);
        $builder->insert([]);
    }

    public function testInsertThrowsExceptionForMismatchedColumns(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("All records passed to insert() must have the same set of columns.");
        
        $database = $this->createStub(Database::class);
        $builder = $this->createBuilder($database);
        $builder->insert([
            ["name" => "John Doe", "email" => "john@example.com"],
            ["name" => "Jane Doe", "age" => 30], // Different columns
        ]);
    }

    public function testInsertNormalizesNonNumericKeyToSingleRow(): void {
        $database = $this->createDatabaseMock();
        
        // Should treat associative array as single row
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->stringContains("VALUES (:name__row1,:email__row1)"),
                $this->anything()
            )
            ->willReturn(1);
        
        $database->method("getLastInsertedId")
            ->willReturn(789);
        
        $builder = $this->createBuilder($database);
        $result = $builder->insert([
            "name" => "Test User",
            "email" => "test@example.com",
        ]);
        
        $this->assertSame(789, $result);
    }

    public function testInsertWithMultipleColumnsGeneratesCorrectPlaceholders(): void {
        $database = $this->createDatabaseMock();
        
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("INSERT INTO test_table\n(id,name,email,age)\nVALUES (:id__row1,:name__row1,:email__row1,:age__row1);"),
                $this->equalTo([
                    "id__row1" => 1,
                    "name__row1" => "John Doe",
                    "email__row1" => "john@example.com",
                    "age__row1" => 30,
                ])
            )
            ->willReturn(1);
        
        $database->method("getLastInsertedId")
            ->willReturn(1);
        
        $builder = $this->createBuilder($database);
        $builder->insert([
            "id" => 1,
            "name" => "John Doe",
            "email" => "john@example.com",
            "age" => 30,
        ]);
    }

    public function testMultiRowInsertWithThreeRowsGeneratesCorrectSQL(): void {
        $database = $this->createDatabaseMock();
        
        $database->expects($this->once())
            ->method("exec")
            ->with(
                $this->equalTo("INSERT INTO test_table\n(name,email)\nVALUES (:name__row1,:email__row1),(:name__row2,:email__row2),(:name__row3,:email__row3);"),
                $this->equalTo([
                    "name__row1" => "User 1",
                    "email__row1" => "user1@example.com",
                    "name__row2" => "User 2",
                    "email__row2" => "user2@example.com",
                    "name__row3" => "User 3",
                    "email__row3" => "user3@example.com",
                ])
            )
            ->willReturn(3);
        
        $builder = $this->createBuilder($database);
        $result = $builder->insert([
            ["name" => "User 1", "email" => "user1@example.com"],
            ["name" => "User 2", "email" => "user2@example.com"],
            ["name" => "User 3", "email" => "user3@example.com"],
        ]);
        
        $this->assertNull($result);
    }
}

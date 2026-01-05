<?php

declare(strict_types=1);

namespace JPI\Database\Query\Tests;

final class CountTest extends BaseTestCase {

    public function testBasicCount(): void {
        $database = $this->createDatabase();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("selectFirst")
            ->with(
                $this->equalTo("SELECT COUNT(*) as count\nFROM users\nLIMIT 1;"),
                $this->equalTo([])
            )
            ->willReturn(["count" => 42])
        ;

        $result = $this->createBuilder($database)->count();

        $this->assertSame(42, $result);
    }

    public function testCountWithColumn(): void {
        $database = $this->createDatabase();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("selectFirst")
            ->with(
                $this->equalTo("SELECT COUNT(id) as count\nFROM users\nLIMIT 1;"),
                $this->equalTo([])
            )
            ->willReturn(["count" => 10])
        ;

        $result = $this->createBuilder($database)->count("id");

        $this->assertSame(10, $result);
    }

    public function testCountWithWhere(): void {
        $database = $this->createDatabase();

        // Check the SQL generated
        $database->expects($this->once())
            ->method("selectFirst")
            ->with(
                $this->equalTo("SELECT COUNT(*) as count\nFROM users\nWHERE active = :active\nLIMIT 1;"),
                $this->equalTo([
                    "active" => 1,
                ])
            )
            ->willReturn(["count" => 5])
        ;

        $result = $this->createBuilder($database)
            ->where("active", "=", 1)
            ->count();

        $this->assertSame(5, $result);
    }
}

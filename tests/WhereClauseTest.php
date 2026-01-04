<?php

declare(strict_types=1);

namespace JPI\Database\Query\Tests;

use JPI\Database\Query\Builder;
use JPI\Database\Query\Clause\Where;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

final class WhereClauseTest extends TestCase {

    /**
     * Helper method to access protected params property using reflection
     */
    private function getParams(Builder $builder): array {
        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty("params");
        $property->setAccessible(true);
        return $property->getValue($builder);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testAnd(): void {
        // Empty
        $builder = $this->createPartialMock(Builder::class, []);
        $this->assertSame("", (string)$builder->newAndCondition());
        $this->assertEmpty($this->getParams($builder));

        // Basic single manual where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = $builder->newAndCondition()->where("column = 1");
        $this->assertSame("column = 1", (string)$where);
        $this->assertEmpty($this->getParams($builder));

        // Basic single = where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = $builder->newAndCondition()->where("column", "=", 1);
        $this->assertSame("column = :column", (string)$where);
        $this->assertSame(["column" => 1], $this->getParams($builder));

        // Multiple
        $builder = $this->createPartialMock(Builder::class, []);
        $where = $builder->newAndCondition()
            ->where("column", "=", 4)
            ->where("column", "IN", [5, 6]);
        $this->assertSame("(column = :column AND column IN (:column_1, :column_2))", (string)$where);
        $this->assertSame(
            [
                "column" => 4,
                "column_1" => 5,
                "column_2" => 6,
            ],
            $this->getParams($builder)
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testOr(): void {
        // Empty
        $builder = $this->createPartialMock(Builder::class, []);
        $this->assertSame("", (string)$builder->newOrCondition());
        $this->assertEmpty($this->getParams($builder));

        // Basic single manual where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = $builder->newOrCondition()->where("column = 1");
        $this->assertSame("column = 1", (string)$where);
        $this->assertEmpty($this->getParams($builder));

        // Basic single = where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = $builder->newOrCondition()->where("column", "=", 1);
        $this->assertSame("column = :column", (string)$where);
        $this->assertSame(["column" => 1], $this->getParams($builder));

        // Multiple
        $builder = $this->createPartialMock(Builder::class, []);
        $where = $builder->newOrCondition()
            ->where("column", "=", 4)
            ->where("column", "IN", [5, 6]);
        $this->assertSame("(column = :column OR column IN (:column_1, :column_2))", (string)$where);
        $this->assertSame(
            [
                "column" => 4,
                "column_1" => 5,
                "column_2" => 6,
            ],
            $this->getParams($builder)
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testClause(): void {
        // Empty
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $this->assertSame("", (string)$where);
        $this->assertEmpty($this->getParams($builder));

        // Basic single manual where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column = 1");
        $this->assertSame("WHERE column = 1", (string)$where);
        $this->assertEmpty($this->getParams($builder));

        // Basic single = where
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column", "=", 1);
        $this->assertSame("WHERE column = :column", (string)$where);
        $this->assertSame(["column" => 1], $this->getParams($builder));

        // Multiple
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column", "=", 4);
        $where->where("column", "IN", [5, 6]);
        $this->assertSame("WHERE column = :column AND column IN (:column_1, :column_2)", (string)$where);
        $this->assertSame(
            [
                "column" => 4,
                "column_1" => 5,
                "column_2" => 6,
            ],
            $this->getParams($builder)
        );

        // Multiple + inner or
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column", "=", 7);
        $where->where(
            $builder->newOrCondition()
                ->where("column = 8")
                ->where("column = 9")
        );
        $this->assertSame("WHERE column = :column AND (column = 8 OR column = 9)", (string)$where);
        $this->assertSame(["column" => 7], $this->getParams($builder));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testOperators(): void {
        // Single value array should use = instead of IN
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column", "IN", [1]);
        $this->assertSame("WHERE column = :column", (string)$where);
        $this->assertSame(["column" => 1], $this->getParams($builder));

        // IN
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column", "IN", [1, 2]);
        $this->assertSame("WHERE column IN (:column_1, :column_2)", (string)$where);
        $this->assertSame(
            [
                "column_1" => 1,
                "column_2" => 2,
            ],
            $this->getParams($builder)
        );

        // NOT IN
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column", "NOT IN", [7, 8, 9]);
        $this->assertSame("WHERE column NOT IN (:column_1, :column_2, :column_3)", (string)$where);
        $this->assertSame(
            [
                "column_1" => 7,
                "column_2" => 8,
                "column_3" => 9,
            ],
            $this->getParams($builder)
        );

        // BETWEEN
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column_one", "BETWEEN", [100, 200]);
        $this->assertSame("WHERE column_one BETWEEN :column_one_1 AND :column_one_2", (string)$where);
        $this->assertSame([
            "column_one_1" => 100,
            "column_one_2" => 200,
        ], $this->getParams($builder));

        // Multiple BETWEEN
        $builder = $this->createPartialMock(Builder::class, []);
        $where = new Where($builder);
        $where->where("column_one", "BETWEEN", [1, 2]);
        $where->where("column_two", "BETWEEN", [3, 4]);
        $this->assertSame("WHERE column_one BETWEEN :column_one_1 AND :column_one_2 AND column_two BETWEEN :column_two_1 AND :column_two_2", (string)$where);
        $this->assertSame([
            "column_one_1" => 1,
            "column_one_2" => 2,
            "column_two_1" => 3,
            "column_two_2" => 4,
        ], $this->getParams($builder));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSubqueryWithEqualsOperator(): void {
        // Basic subquery with = operator
        $database = $this->createMock(\JPI\Database::class);
        $builder = new Builder($database, "main_table");
        $subquery = new Builder($database, "sub_table");
        $subquery->column("sub_column");
        $subquery->where("sub_column", "=", 10);

        $where = new Where($builder);
        $where->where("main_column", "=", $subquery);
        
        $expected = "WHERE main_column = (SELECT sub_column\nFROM sub_table\nWHERE sub_column = :sub_column)";
        $this->assertSame($expected, (string)$where);
        // Note: Subquery params are not automatically merged into main builder
        $this->assertEmpty($this->getParams($builder));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSubqueryWithInOperator(): void {
        // Subquery with IN operator
        $database = $this->createMock(\JPI\Database::class);
        $builder = new Builder($database, "users");
        $subquery = new Builder($database, "orders");
        $subquery->column("user_id");
        $subquery->where("status", "=", "completed");

        $where = new Where($builder);
        $where->where("id", "IN", $subquery);
        
        $expected = "WHERE id IN (SELECT user_id\nFROM orders\nWHERE status = :status)";
        $this->assertSame($expected, (string)$where);
        // Note: Subquery params are not automatically merged into main builder
        $this->assertEmpty($this->getParams($builder));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSubqueryWithNotInOperator(): void {
        // Subquery with NOT IN operator
        $database = $this->createMock(\JPI\Database::class);
        $builder = new Builder($database, "products");
        $subquery = new Builder($database, "banned_products");
        $subquery->column("product_id");

        $where = new Where($builder);
        $where->where("id", "NOT IN", $subquery);
        
        $expected = "WHERE id NOT IN (SELECT product_id\nFROM banned_products)";
        $this->assertSame($expected, (string)$where);
        $this->assertEmpty($this->getParams($builder));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSubqueryInAndCondition(): void {
        // Subquery in AND condition
        $database = $this->createMock(\JPI\Database::class);
        $builder = new Builder($database, "employees");
        $subquery = new Builder($database, "departments");
        $subquery->column("id");
        $subquery->where("name", "=", "Engineering");

        $where = $builder->newAndCondition()
            ->where("active", "=", 1)
            ->where("department_id", "IN", $subquery);
        
        $expected = "(active = :active AND department_id IN (SELECT id\nFROM departments\nWHERE name = :name))";
        $this->assertSame($expected, (string)$where);
        // Only main query params are in main builder
        $this->assertSame([
            "active" => 1,
        ], $this->getParams($builder));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSubqueryInOrCondition(): void {
        // Subquery in OR condition
        $database = $this->createMock(\JPI\Database::class);
        $builder = new Builder($database, "posts");
        $subquery = new Builder($database, "featured_posts");
        $subquery->column("post_id");

        $where = $builder->newOrCondition()
            ->where("views", ">", 1000)
            ->where("id", "IN", $subquery);
        
        $expected = "(views > :views OR id IN (SELECT post_id\nFROM featured_posts))";
        $this->assertSame($expected, (string)$where);
        $this->assertSame(["views" => 1000], $this->getParams($builder));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMultipleSubqueries(): void {
        // Multiple subqueries in same WHERE clause
        $database = $this->createMock(\JPI\Database::class);
        $builder = new Builder($database, "users");
        
        $subquery1 = new Builder($database, "premium_users");
        $subquery1->column("user_id");
        
        $subquery2 = new Builder($database, "banned_users");
        $subquery2->column("user_id");

        $where = new Where($builder);
        $where->where("id", "IN", $subquery1);
        $where->where("id", "NOT IN", $subquery2);
        
        $expected = "WHERE id IN (SELECT user_id\nFROM premium_users) AND id NOT IN (SELECT user_id\nFROM banned_users)";
        $this->assertSame($expected, (string)$where);
        $this->assertEmpty($this->getParams($builder));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testComplexSubquery(): void {
        // Complex subquery with WHERE, ORDER BY, and LIMIT
        $database = $this->createMock(\JPI\Database::class);
        $builder = new Builder($database, "articles");
        
        $subquery = new Builder($database, "popular_articles");
        $subquery->column("article_id");
        $subquery->where("views", ">", 5000);
        $subquery->where("published", "=", 1);  // Use 1 instead of true for type compatibility
        $subquery->orderBy("views", false);  // false = DESC
        $subquery->limit(10);

        $where = new Where($builder);
        $where->where("id", "IN", $subquery);
        $where->where("category", "=", "tech");
        
        $expected = "WHERE id IN (SELECT article_id\nFROM popular_articles\nWHERE views > :views AND published = :published\nORDER BY views DESC\nLIMIT 10) AND category = :category";
        $this->assertSame($expected, (string)$where);
        // Only main query params are in main builder
        $this->assertSame([
            "category" => "tech",
        ], $this->getParams($builder));
    }
}

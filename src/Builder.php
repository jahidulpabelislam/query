<?php

declare(strict_types=1);

namespace JPI\Database\Query;

use JPI\Database;
use JPI\Database\Query\Clause\Join as JoinClause;
use JPI\Database\Query\Clause\OrderBy as OrderByClause;
use JPI\Database\Query\Clause\Where as WhereClause;
use JPI\Database\Query\Clause\Where\AndCondition;
use JPI\Database\Query\Clause\Where\OrCondition;
use JPI\Database\Query\Result\Collection;
use JPI\Database\Query\Result\CollectionInterface;
use JPI\Database\Query\Result\PaginatedCollection;
use JPI\Database\Query\Result\PaginatedCollectionInterface;
use JPI\Database\Query\Result\Row;
use Stringable;
use Traversable;

/**
 * Query builder. Allows building SQL queries also executing them and receiving in appropriate format.
 */
class Builder implements WhereableInterface, ParamableInterface {

    use ParamableTrait;

    /** @var class-string<CollectionInterface> */
    protected static string $collectionClass = Collection::class;

    /** @var class-string<PaginatedCollectionInterface> */
    protected static string $paginatedCollectionClass = PaginatedCollection::class;

    protected array $columns = [];

    protected array $joins = [];

    protected WhereClause $where;

    protected OrderByClause $orderBy;

    protected ?int $limit = null;

    protected ?int $page = null;

    public function __construct(
        protected Database $database,
        protected ?string $table = null
    ) {
        $this->where = new WhereClause($this);
        $this->orderBy = new OrderByClause($this);
    }

    public function table(string $table, ?string $alias = null): static {
        $this->table = $alias ? "$table as $alias" : $table;
        return $this;
    }

    public function column(string $column, ?string $alias = null): static {
        if ($column === "*") {
            $this->columns[] = "$this->table.*";
        }
        else {
            $this->columns[] = $alias ? "$column as $alias" : $column;
        }

        return $this;
    }

    public function newJoinClause(string $table, string $type = "INNER"): JoinClause {
        return new JoinClause($this, $table, $type);
    }

    public function join(
        JoinClause|string $joinOrTable,
        ?string $on = null,
        string $type = "INNER"
    ): static {
        if (!$joinOrTable instanceof JoinClause) {
            $joinOrTable = $this->newJoinClause($joinOrTable, $type)->on($on);
        }
        $this->joins[] = $joinOrTable;

        return $this;
    }

    public function rightJoin(string $table, string $on): static {
        return $this->join($table, $on, "RIGHT");
    }

    public function leftJoin(string $table, string $on): static {
        return $this->join($table, $on, "LEFT");
    }

    public function where(
        Stringable|string $columnOrExpression,
        ?string $operator = null,
        Builder|Stringable|string|int|float|array|null $valueOrPlaceholder = null
    ): static {
        $this->where->where($columnOrExpression, $operator, $valueOrPlaceholder);
        return $this;
    }

    public function orderBy(string $column, bool $ascDirection = true): static {
        $this->orderBy[] = "$column " . ($ascDirection ? "ASC" : "DESC");
        return $this;
    }

    public function page(int $page): static {
        $this->page = $page;
        return $this;
    }

    public function limit(int $limit, ?int $page = null): static {
        if ($page !== null) {
            $this->page($page);
        }

        $this->limit = $limit;
        return $this;
    }

    public function newAndCondition(): AndCondition {
        return new AndCondition($this);
    }

    public function newOrCondition(): OrCondition {
        return new OrCondition($this);
    }

    /**
     * Convenient function to pluck/get out the single value from an array if it's the only value.
     * Then build a string value if an array.
     */
    public static function arrayToString(Traversable|array $value, string $separator = ","): string {
        $value = $value instanceof Traversable ? iterator_to_array($value) : $value;
        if (count($value) === 1) {
            return (string)array_shift($value);
        }

        return implode($separator, $value);
    }

    protected function generateLimitClause(): ?string {
        $limit = $this->limit;
        if (!$limit) {
            return null;
        }

        $clause = "LIMIT $limit";

        // Generate an offset, using limit & page values
        $page = $this->page;
        if ($page > 1) {
            $offset = $limit * ($page - 1);
            $clause .= " OFFSET $offset";
        }

        return $clause;
    }

    public static function buildQuery(array $parts): string {
        $query = implode("\n", $parts);
        $query .= ";";

        return $query;
    }

    public function getSelectQuery(): string {
        $columns = $this->columns;
        $joins = $this->joins;

        $columns = !empty($columns) ? static::arrayToString($columns) : "*";
        $joins = !empty($joins) ? static::arrayToString($joins, " ") : null;

        return static::buildQuery(array_filter([
            "SELECT $columns",
            "FROM $this->table",
            $joins,
            (string)$this->where,
            (string)$this->orderBy,
            $this->generateLimitClause(),
        ]));
    }

    /** @return class-string<ResultInterface> */
    public function getResultClass(): string {
        return Row::class;
    }

    public function createResults(array $rows): array {
        $resultClass = $this->getResultClass();
        $results = [];

        foreach ($rows as $row) {
            $results[] = $resultClass::loadFromDatabaseRow($row);
        }

        return $results;
    }

    public function select(bool $withPagination = true): CollectionInterface|PaginatedCollectionInterface|ResultInterface|null {
        $limit = $this->limit;

        $query = $this->getSelectQuery();

        if ($limit === 1) {
            $result = $this->database->selectFirst($query, $this->params);

            if (!$result) {
                return null;
            }

            return $this->getResultClass()::loadFromDatabaseRow($result);
        }

        $rows = $this->database->selectAll($query, $this->params);

        if (!$limit || ($limit && !$withPagination)) {
            return new static::$collectionClass($this->createResults($rows));
        }

        $page = $this->page ?? 1;

        $count = count($rows);

        /**
         * Do a DB query to get total count if:
         *    - none found on a page greater than 1
         *    - count is the limit
         * Else we can work out the total
         */
        if ((!$count && $page > 1) || $count === $limit) {
            // Replace the SELECT part in query with a simple count
            $totalCount = (clone $this)->count();
        }
        else {
            $totalCount = $limit * ($page - 1) + $count;
        }

        return new static::$paginatedCollectionClass($this->createResults($rows), $totalCount, $limit, $page);
    }

    public function count(string $column = "*"): int {
        // Clear/reset
        $this->columns = [];
        $this->orderBy->clear();

        $this->column("COUNT($column)", "count");
        $this->limit(1, 1);

        $row = $this->database->selectFirst($this->getSelectQuery(), $this->params);

        return (int)$row["count"];
    }

    public function insert(array $values): ?int {
        $this->params($values);

        $sets = [];
        foreach (array_keys($values) as $column) {
            $sets[] = "$column = :$column";
        }

        $rowsAffected = $this->database->exec(
            static::buildQuery(array_filter([
                "INSERT INTO $this->table",
                "SET " . static::arrayToString($sets),
            ])),
            $this->params
        );

        if ($rowsAffected === 0) {
            return null;
        }

        return $this->database->getLastInsertedId();
    }

    public function update(array $values): int {
        $this->params($values);

        $sets = [];
        foreach (array_keys($values) as $column) {
            $sets[] = "$column = :$column";
        }

        return $this->database->exec(
            static::buildQuery(array_filter([
                "UPDATE $this->table",
                "SET " . static::arrayToString($sets),
                (string)$this->where,
                (string)$this->orderBy,
                $this->generateLimitClause(),
            ])),
            $this->params
        );
    }

    public function delete(): int {
        $rowsDeleted = $this->database->exec(
            static::buildQuery(array_filter([
                "DELETE FROM $this->table",
                (string)$this->where,
                (string)$this->orderBy,
                $this->generateLimitClause(),
            ])),
            $this->params
        );
        return $rowsDeleted;
    }

    public function __clone() {
        $this->where = clone $this->where;
        $this->orderBy = clone $this->orderBy;
    }
}

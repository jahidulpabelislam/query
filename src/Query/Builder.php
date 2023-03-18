<?php

declare(strict_types=1);

namespace JPI\Database\Query;

use JPI\Database;
use JPI\Database\Collection;
use JPI\Database\PaginatedCollection;
use JPI\Database\Query\Clause\OrderBy as OrderByClause;
use JPI\Database\Query\Clause\Where as WhereClause;

/**
 * Query builder. Allows building SQL queries also executing them and receiving in appropriate format.
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2012-2023 JPI
 */
class Builder implements WhereableInterface, ParamableInterface {

    use ParamableTrait;

    protected array $columns = [];

    protected WhereClause $where;

    protected OrderByClause $orderBy;

    protected ?int $limit = null;

    protected ?int $page = null;

    public function __construct(protected Database $database, protected ?string $table = null) {
        $this->where = new WhereClause($this);
        $this->orderBy = new OrderByClause($this);
    }

    public function table(string $table, string $alias = null): Builder {
        $this->table = $alias ? "$table as $alias" : $table;
        return $this;
    }

    public function column(string $column, string $alias = null): Builder {
        if ($column === "*") {
            $this->columns[] = "$this->table.*";
        }
        else {
            $this->columns[] = $alias ? "$column as $alias" : $column;
        }

        return $this;
    }

    public function where(string $whereOrColumn, ?string $expression = null, string|int|float|array $valueOrPlaceholder = null): WhereableInterface {
        $this->where->where($whereOrColumn, $expression, $valueOrPlaceholder);
        return $this;
    }

    public function orderBy(string $column, string $direction = "ASC"): Builder {
        $this->orderBy[] = "$column $direction";
        return $this;
    }

    public function page(int $page): Builder {
        $this->page = $page;
        return $this;
    }

    public function limit(int $limit, int $page = null): Builder {
        if (!is_null($page)) {
            $this->page($page);
        }

        $this->limit = $limit;
        return $this;
    }

    /**
     * Convenient function to pluck/get out the single value from an array if it's the only value.
     * Then build a string value if an array.
     *
     * @param $value string[]|string|null
     * @param $separator string
     * @return string
     */
    public static function arrayToString(array $value, string $separator = ","): string {
        if (count($value) === 1) {
            return array_shift($value);
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

        $columns = count($columns) ? static::arrayToString($columns) : "*";

        return static::buildQuery(array_filter([
            "SELECT $columns",
            "FROM {$this->table}",
            (string) $this->where,
            (string) $this->orderBy,
            $this->generateLimitClause(),
        ]));
    }

    public function createCollectionFromResult(array $rows) {
        return new Collection($rows);
    }

    public function createPaginatedCollectionFromResult(array $rows, int $totalCount, int $limit, int $page) {
        return new PaginatedCollection($rows, $totalCount, $limit, $page);
    }

    /**
     * @return PaginatedCollection|Collection|array|null
     */
    public function select() {
        $limit = $this->limit;

        $query = $this->getSelectQuery();

        if ($limit === 1) {
            return $this->database->selectFirst($query, $this->params);
        }

        $rows = $this->database->selectAll($query, $this->params);

        if (!$limit) {
            return $this->createCollectionFromResult($rows);
        }

        $page = $this->page ?? 1;

        $count = count($rows);

        /**
         * Do a DB query to get total count if:
         *    - none found on a specific page than 1
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

        return $this->createPaginatedCollectionFromResult($rows, $totalCount, $limit, $page);
    }

    public function count(): int {
        // Clear/reset
        $this->columns = [];
        $this->orderBy = new Database\Query\Clause\OrderBy($this);

        $this->column("COUNT(*)", "count");
        $this->limit(1, 1);

        $row = $this->database->selectFirst($this->getSelectQuery(), $this->params);

        return $row["count"] ?? 0;
    }

    public function insert(array $values): ?int {
        $this->params($values);

        $sets = [];
        foreach (array_keys($values) as $column) {
            $sets[] = "$column = :$column";
        }

        $rowsAffected = $this->database->exec(
            static::buildQuery(array_filter([
                "INSERT INTO {$this->table}",
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
                "UPDATE {$this->table}",
                "SET " . static::arrayToString($sets),
                (string) $this->where
            ])),
            $this->params
        );
    }

    public function delete(): int {
        $rowsDeleted = $this->database->exec(
            static::buildQuery(array_filter([
                "DELETE FROM {$this->table}",
                (string) $this->where
            ])),
            $this->params
        );
        return $rowsDeleted;
    }
}

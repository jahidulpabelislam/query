<?php

/**
 * Query builder. Builds the SQL queries and executes/runs them and returns in appropriate format.
 *
 * PHP version 7.1+
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version v1.0.0
 * @copyright 2010-2021 JPI
 */

namespace JPI\Database;

class Query {

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $table = null;

    /**
     * @var array|null
     */
    protected $columns = null;

    /**
     * @var array
     */
    protected $wheres = [];

    /**
     * @var array
     */
    protected $orderBys = [];

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var int|null
     */
    protected $limit = null;

    /**
     * @var int|null
     */
    protected $page = null;

    /**
     * @param $connection Connection
     * @param $table string|null
     */
    public function __construct(Connection $connection, string $table = null) {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * @return Generator
     */
    protected function getGenerator(): Generator {
        return new Generator($this);
    }

    /**
     * @param $part string
     * @return mixed
     */
    public function getPart(string $part) {
        return $this->{$part} ?? null;
    }

    /**
     * @param $table string
     * @param $alias string
     * @return $this
     */
    public function table(string $table, string $alias): Query {
        if (!is_null($alias)) {
            $table = "$table as $alias";
        }

        $this->table = $table;
        return $this;
    }

    /**
     * @param $column string
     * @param $alias string|null
     * @return $this
     */
    public function column(string $column, string $alias = null): Query {
        if ($column === '*') {
            $this->columns = null;
        } else {
            $this->columns = Utilities::initArray($this->columns);
            if (!is_null($alias)) {
                $column = "$column as $alias";
            }
            $this->columns[] = $column;
        }

        return $this;
    }

    /**
     * @param $where string
     * @return $this
     */
    public function where(string $where): Query {
        $expression = '=';

        $args = func_get_args();

        // (column, expression, value)
        if (isset($args[2])) {
            [$where, $expression, $value] = $args;
        }

        // (column, expression, value) OR (column, value)
        if (isset($args[1])) {
            $value = $value ?? $args[1];
            $this->param($where, $value);
            $where = "$where $expression $value";
        }

        $this->wheres[] = $where;
        return $this;
    }

    /**
     * @param $column string
     * @param $direction string
     * @return $this
     */
    public function orderBy(string $column, $direction = 'DESC'): Query {
        $orderBy = "$column $direction";
        $this->orderBys[] = $orderBy;
        return $this;
    }

    /**
     * @param $limit int
     * @return $this
     */
    public function limit(int $limit): Query {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param $page int
     * @return $this
     */
    public function page(int $page): Query {
        $this->page = $page;
        return $this;
    }

    /**
     * @param $key string
     * @param $value string|int|float
     * @return $this
     */
    public function param(string $key, $value): Query {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * @param $params array
     * @return $this
     */
    public function params(array $params): Query {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * @param $parts string[]
     * @param $function string
     * @return array[]|array|int|null
     */
    private function execute(array $parts, string $function = "execute") {
        return $this->connection->{$function}(Utilities::buildQuery($parts), $this->params);
    }

    /**
     * @return Collection|array|null
     */
    public function select() {
        $limit = $this->limit;
        $page = $this->page;

        $parts = $this->getGenerator()->select();

        if ($limit === 1) {
            return $this->execute($parts, "getOne");
        }

        $rows = $this->execute($parts, "getAll");

        $totalCount = null;
        if ($limit) {
            $count = count($rows);

            /**
             * Do a DB query to get total count if:
             *    - none found on a specific page than 1
             *    - count is the limit
             * Else we can work out the total
             */
            if ((!$count && $page > 1) || $count === $limit) {
                // Replace the SELECT part in query with a simple count
                $totalCount = (clone $this)
                    ->count();
            }
            else {
                $totalCount = $limit * ($page - 1) + $count;
            }
        }

        return new Collection($rows, $totalCount, $limit, $page);
    }

    /**
     * @return int
     */
    public function count(): int {
        // Clear/reset
        $this->columns = [];
        $this->orderBys = [];

        $this->column('COUNT(*)', 'count')
            ->limit(1)
        ;
        $row = $this->select();
        return $row["count"] ?? 0;
    }

    /**
     * @param $values array
     * @param $isInsert bool
     * @return string[]
     */
    protected function generateInsertOrUpdateParts(array $values, bool $isInsert = true): array {
        $this->params($values);

        return $this->getGenerator()->insertOrUpdate($values, $isInsert);
    }

    /**
     * @param $values array
     * @param $isInsert bool
     * @return int
     */
    protected function insertOrUpdate(array $values, bool $isInsert = true): int {
        return $this->execute($this->generateInsertOrUpdateParts($values, $isInsert));
    }

    /**
     * @param $values array
     * @return int|null
     */
    public function insert(array $values): ?int {
        $rowsAffected = $this->insertOrUpdate($values);
        if ($rowsAffected > 0) {
            return $this->connection->getLastInsertedId();
        }

        return null;
    }

    /**
     * @param $values array
     * @return int
     */
    public function update(array $values): int {
        return $this->insertOrUpdate($values, false);
    }

    /**
     * @return string[]
     */
    protected function generateDeleteParts(): array {
        return $this->getGenerator()->delete();
    }

    /**
     * @return int
     */
    public function delete(): int {
        $rowsDeleted = $this->execute($this->generateDeleteParts());
        return $rowsDeleted;
    }

}

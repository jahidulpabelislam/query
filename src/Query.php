<?php

namespace JPI\Database;

use JPI\Database;

/**
 * Builds the SQL queries and executes/runs them and returns in appropriate format.
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2012-2022 JPI
 */
class Query {

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var string
     */
    protected $table;

    /**
     * @param Database $database
     * @param string $table
     */
    public function __construct(Database $database, string $table) {
        $this->database = $database;
        $this->table = $table;
    }

    /**
     * Convenient function to pluck/get out the single value from an array if it's the only value.
     * Then build a string value if an array.
     *
     * @param string[]|string|null $value
     * @param string $separator
     * @return string
     */
    private static function arrayToString($value, string $separator = ", "): string {
        if ($value && is_array($value)) {
            if (count($value) > 1) {
                return implode($separator, $value);
            }

            $value = array_shift($value);
        }

        if (is_string($value)) {
            return $value;
        }

        return "";
    }

    /**
     * @param string[]|string|int|null $where
     * @param array $params
     * @return array [string|null, array]
     */
    private static function generateWhereClause($where, array $params = []): array {
        if ($where) {
            if (is_numeric($where)) {
                $params["id"] = (int)$where;
                $where = "id = :id";
            }

            $where = static::arrayToString($where, " AND ");

            return [
                "WHERE $where",
                $params,
            ];
        }

        return [
            null,
            $params,
        ];
    }

    /**
     * @param string $table
     * @param string[]|string|null $columns
     * @param string[]|string|int|null $where
     * @param array $params
     * @param string[]|string|null $orderBy
     * @param int|null $limit
     * @param int|null $page
     * @return array [array, array]
     */
    protected static function generateSelectQuery(
        string $table,
        $columns = "*",
        $where = null,
        array $params = [],
        $orderBy = null,
        ?int $limit = null,
        ?int $page = null
    ): array {
        $columns = $columns ?: "*";
        $columns = static::arrayToString($columns);

        $sqlParts = [
            "SELECT $columns",
            "FROM $table",
        ];

        [$whereClause, $params] = static::generateWhereClause($where, $params);
        if ($whereClause) {
            $sqlParts[] = $whereClause;

            if (is_numeric($where)) {
                $sqlParts[] = "LIMIT 1";
                return [$sqlParts, $params];
            }
        }

        $orderBy = static::arrayToString($orderBy);
        if ($orderBy) {
            $sqlParts[] = "ORDER BY $orderBy";
        }

        if ($limit) {
            $limitPart = "LIMIT $limit";

            // Generate a offset, using limit & page values
            if ($page > 1) {
                $offset = $limit * ($page - 1);
                $limitPart .= " OFFSET $offset";
            }

            $sqlParts[] = $limitPart;
        }

        return [$sqlParts, $params];
    }

    /**
     * Get the page to use for a SQL query
     * Can specify the page and it will make sure it is valid
     *
     * @param int|string|null $page
     * @return int|null
     */
    protected static function getPage($page = null): ?int {
        if (is_numeric($page)) {
            $page = (int)$page;
        }

        // If invalid use page 1
        if (!$page || $page < 1) {
            $page = 1;
        }

        return $page;
    }

    /**
     * @param string[]|string|null $columns
     * @param string[]|string|int|null $where
     * @param array $params
     * @param string[]|string|null $orderBy
     * @param int|null $limit
     * @param int|string|null $page
     * @return \JPI\Database\Collection|\JPI\Database\PaginatedCollection|array|null
     */
    public function select(
        $columns = "*",
        $where = null,
        array $params = [],
        $orderBy = null,
        ?int $limit = null,
        $page = null
    ) {
        $page = $limit ? static::getPage($page) : null;

        [$sqlParts, $params] = static::generateSelectQuery(
            $this->table,
            $columns,
            $where,
            $params,
            $orderBy,
            $limit,
            $page
        );

        $query = implode(" ", $sqlParts) . ";";

        if (($where && is_numeric($where)) || $limit === 1) {
            return $this->database->selectFirst($query, $params);
        }

        $rows = $this->database->selectAll($query, $params);

        $count = count($rows);

        if (!$limit) {
            return new Collection($rows);
        }

        /**
         * Do a DB query to get total count if:
         *    - none found on a specific page than 1
         *    - count is the limit
         * Else we can work out the total
         */
        if ((!$count && $page > 1) || $count === $limit) {
            // Replace the SELECT part in query with a simple count
            $sqlParts[0] = "SELECT COUNT(*) as count";

            array_pop($sqlParts); // Remove the LIMIT part in query

            // Remove the ORDER BY part in query if added
            if ($orderBy) {
                array_pop($sqlParts);
            }

            $row = $this->database->selectFirst(implode(" ", $sqlParts) . ";", $params);
            $totalCount = $row["count"] ?? 0;
        }
        else {
            $totalCount = $limit * ($page - 1) + $count;
        }

        return new PaginatedCollection($rows, $totalCount, $limit, $page);
    }

    /**
     * @param string[]|string|int|null $where
     * @param array $params
     * @return int
     */
    public function count($where = null, array $params = []): int {
        $row = $this->select("COUNT(*) as total_count", $where, $params, null, 1);
        return $row["total_count"] ?? 0;
    }

    /**
     * @param array $values
     * @param string[]|string|int|null $where
     * @param array $params
     * @param bool $isInsert
     * @return int
     */
    protected function insertOrUpdate(array $values, $where = null, array $params = [], bool $isInsert = true): int {
        $params = array_merge($params, $values);

        $valuesQueries = [];
        foreach (array_keys($values) as $column) {
            $valuesQueries[] = "$column = :$column";
        }
        $valuesQuery = static::arrayToString($valuesQueries);

        $sqlParts = [
            ($isInsert ? "INSERT INTO" : "UPDATE") . " $this->table",
            "SET $valuesQuery",
        ];

        if (!$isInsert) {
            [$whereClause, $params] = static::generateWhereClause($where, $params);
            if ($whereClause) {
                $sqlParts[] = $whereClause;
            }
        }

        return $this->database->exec(implode(" ", $sqlParts) . ";", $params);
    }

    /**
     * @param array $values
     * @return int|null
     */
    public function insert(array $values): ?int {
        $rowsAffected = $this->insertOrUpdate($values);
        if ($rowsAffected > 0) {
            return $this->database->getLastInsertedId();
        }

        return null;
    }

    /**
     * @param array $values
     * @param string[]|string|int|null $where
     * @param array $params
     * @return int
     */
    public function update(array $values, $where = null, array $params = []): int {
        return $this->insertOrUpdate($values, $where, $params, false);
    }

    /**
     * @param string[]|string|int|null $where
     * @param array $params
     * @return int
     */
    public function delete($where = null, array $params = []): int {
        $sqlParts = ["DELETE FROM $this->table"];

        [$whereClause, $params] = static::generateWhereClause($where, $params);
        if ($whereClause) {
            $sqlParts[] = $whereClause;
        }

        $rowsDeleted = $this->database->exec(implode(" ", $sqlParts) . ";", $params);

        return $rowsDeleted;
    }
}

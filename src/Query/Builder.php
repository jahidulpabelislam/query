<?php

declare(strict_types=1);

namespace JPI\Database\Query;

use JPI\Database;
use JPI\Database\Collection;
use JPI\Database\PaginatedCollection;

/**
 * Query builder. Allows building SQL queries also executing them and receiving in appropriate format.
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2012-2023 JPI
 */
class Builder implements WhereableInterface, ParamableInterface {

    use WhereableTrait;
    use ParamableTrait;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var string|null
     */
    protected $table = null;

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @var array
     */
    protected $orderBys = [];

    /**
     * @var int|null
     */
    protected $limit = null;

    /**
     * @var int|null
     */
    protected $page = null;

    public function __construct(Database $database, string $table = null) {
        $this->database = $database;
        $this->table = $table;
    }

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

    public function table(string $table, string $alias = null): Builder {
        $this->table = $alias ? "$table as $alias" : $table;
        return $this;
    }

    public function column(string $column, string $alias = null): Builder {
        if ($column === "*") {
            $this->columns = [];
        }
        else {
            $this->columns[] = $alias ? "$column as $alias" : $column;
        }

        return $this;
    }

    public function orderBy(string $column, string $direction = "ASC"): Builder {
        $this->orderBys[] = "$column $direction";
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

    public static function buildQuery(array $parts): string {
        $query = implode("\n", $parts);
        $query .= ";";

        return $query;
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

        $query = static::buildQuery($this->getGenerator()->select());

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
        $this->orderBys = [];

        $this->column("COUNT(*)", "count");
        $this->limit(1);

        $row = $this->select();
        return $row["count"] ?? 0;
    }

    public function insert(array $values): ?int {
        $this->params($values);
        $rowsAffected = $this->database->exec(
            static::buildQuery($this->getGenerator()->insert($values)),
            $this->params
        );

        if ($rowsAffected > 0) {
            return null;
        }

        return $this->database->getLastInsertedId();
    }

    public function update(array $values): int {
        $this->params($values);

        return $this->database->exec(
            static::buildQuery($this->getGenerator()->update($values)),
            $this->params
        );
    }

    public function delete(): int {
        $rowsDeleted = $this->database->exec(
            static::buildQuery($this->getGenerator()->delete()),
            $this->params
        );
        return $rowsDeleted;
    }
}

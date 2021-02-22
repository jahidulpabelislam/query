<?php

namespace JPI\Database;

class Generator {

    /**
     * @var Query
     */
    protected $query;

    /**
     * @param $query Query
     */
    public function __construct(Query $query) {
        $this->query = $query;
    }

    /**
     * @param $part string
     * @return null
     */
    protected function getPart(string $part) {
        return $this->query->getPart($part);
    }

    /**
     * @return string|null
     */
    protected function generateWhereClause(): ?string {
        if ($wheres = $this->getPart('wheres')) {
            $wheres = Utilities::arrayToQueryString($wheres, "\n\tAND ");
            return "WHERE $wheres";
        }

        return null;
    }

    /**
     * @return string|null
     */
    protected function generateOrderByClause(): ?string {
        if ($orderBy = $this->getPart('orderBys')) {
            $orderBys = Utilities::arrayToQueryString($orderBy);
            return "ORDER BY $orderBys";
        }

        return null;
    }

    /**
     * @return string|null
     */
    protected function generateLimitClause(): ?string {
        if ($limit = $this->getPart('limit')) {
            $clause = "LIMIT $limit";

            // Generate an offset, using limit & page values
            $page = $this->getPart('page');
            if ($page > 1) {
                $offset = $limit * ($page - 1);
                $clause .= " OFFSET $offset";
            }

            return $clause;
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function select(): array {
        $columns = $this->getPart('columns') ?: "*";
        $columns = Utilities::arrayToQueryString($columns);

        $parts = [
            "SELECT $columns",
            "FROM {$this->getPart('table')}",
        ];

        $where = $this->generateWhereClause();
        if ($where) {
            $parts[] = $where;
        }

        if ($orderBy = $this->generateOrderByClause()) {
            $parts[] = $orderBy;
        }

        if ($limit = $this->generateLimitClause()) {
            $parts[] = $limit;
        }

        return $parts;
    }

    /**
     * @param $values array
     * @param $isInsert bool
     * @return string[]
     */
    public function insertOrUpdate(array $values, bool $isInsert = true): array {
        $valuesQueries = [];
        foreach ($values as $column => $value) {
            $valuesQueries[] = "$column = :$column";
        }
        $valuesQuery = Utilities::arrayToQueryString($valuesQueries);

        $parts = [
            ($isInsert ? "INSERT INTO" : "UPDATE") . " {$this->getPart('table')}",
            "SET $valuesQuery",
        ];

        if (!$isInsert) {
            $where = $this->generateWhereClause();
            if ($where) {
                $parts[] = $where;
            }
        }

        return $parts;
    }

    /**
     * @return string[]
     */
    public function delete(): array {
        $parts = ["DELETE FROM {$this->getPart('table')}"];

        $where = $this->generateWhereClause();
        if ($where) {
            $parts[] = $where;
        }

        return $parts;
    }

}

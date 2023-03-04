<?php

declare(strict_types=1);

namespace JPI\Database\Query;

class Generator {

    /**
     * @var Builder
     */
    protected $builder;

    public function __construct(Builder $builder) {
        $this->builder = $builder;
    }

    /**
     * Convenient function to pluck/get out the single value from an array if it's the only value.
     * Then build a string value if an array.
     *
     * @param $value string[]|string|null
     * @param $separator string
     * @return string
     */
    public static function arrayToString($value, string $separator = ","): string {
        if (is_array($value) && count($value)) {
            if (count($value) === 1) {
                $value = array_shift($value);
            }
            else {
                $separator .= "\n\t";
                $value = "\n\t" . implode($separator, $value);
            }
        }

        if (is_string($value)) {
            return $value;
        }

        return "";
    }

    /**
     * @param $part string
     * @return mixed
     */
    protected function getPart(string $part) {
        return $this->builder->getPart($part);
    }

    protected function generateWhereClause(): ?string {
        $wheres = $this->getPart("wheres");
        if (!$wheres) {
            return null;
        }

        return "WHERE " . static::arrayToString($wheres, " AND ");
    }

    protected function generateOrderByClause(): ?string {
        $orderBy = $this->getPart("orderBys");
        if (!$orderBy) {
            return null;
        }

        return "ORDER BY " . static::arrayToString($orderBy);
    }

    protected function generateLimitClause(): ?string {
        $limit = $this->getPart("limit");
        if (!$limit) {
            return null;
        }

        $clause = "LIMIT $limit";

        // Generate an offset, using limit & page values
        $page = $this->getPart("page");
        if ($page > 1) {
            $offset = $limit * ($page - 1);
            $clause .= " OFFSET $offset";
        }

        return $clause;
    }

    public function select(): array {
        $columns = $this->getPart("columns");

        $columns = count($columns) ? static::arrayToString($columns) : "*";

        return array_filter([
            "SELECT $columns",
            "FROM {$this->getPart("table")}",
            $this->generateWhereClause(),
            $this->generateOrderByClause(),
            $this->generateLimitClause(),
        ]);
    }

    public function insert(array $values): array {
        $sets = [];
        foreach (array_keys($values) as $column) {
            $sets[] = "$column = :$column";
        }

        return array_filter([
            "INSERT INTO {$this->getPart("table")}",
            "SET " . static::arrayToString($sets),
        ]);
    }

    public function update(array $values): array {
        $sets = [];
        foreach (array_keys($values) as $column) {
            $sets[] = "$column = :$column";
        }

        return array_filter([
            "UPDATE {$this->getPart("table")}",
            "SET " . static::arrayToString($sets),
            $this->generateWhereClause()
        ]);
    }

    public function delete(): array {
        return array_filter([
            "DELETE FROM {$this->getPart("table")}",
            $this->generateWhereClause()
        ]);
    }
}

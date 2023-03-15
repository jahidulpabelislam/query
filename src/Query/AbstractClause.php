<?php

declare(strict_types=1);

namespace JPI\Database\Query;

use JPI\Utils\Collection;

abstract class AbstractClause extends Collection {

    protected $clause;
    protected $separator = ",";

    protected $items = [];

    public function __construct(Builder $query) {
        $this->query = $query;
    }

    public function getClause(): string {
        return $this->clause;
    }

    public function getSeparator(): string {
        return $this->separator;
    }

    public function __toString() {
        $count = count($this->items);
        if (!$count) {
            return "";
        }

        $value = $this->query::arrayToString($this->items, " {$this->getSeparator()} ");

        return "$this->clause $value";
    }
}

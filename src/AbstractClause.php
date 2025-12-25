<?php

declare(strict_types=1);

namespace JPI\Database\Query;

use JPI\Utils\Collection;
use Stringable;

abstract class AbstractClause extends Collection implements Stringable {

    protected string $clause;
    protected string $separator = ",";

    public function __construct(protected Builder $query) {
        parent::__construct();
    }

    public function getClause(): string {
        return $this->clause;
    }

    public function getSeparator(): string {
        return $this->separator;
    }

    public function __toString(): string {
        if (empty($this->getItems())) {
            return "";
        }

        $value = $this->query::arrayToString($this, "{$this->getSeparator()} ");

        return "{$this->getClause()} $value";
    }
}

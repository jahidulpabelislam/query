<?php

declare(strict_types=1);

namespace JPI\Database\Query;

use JPI\Utils\Collection;
use Stringable;

abstract class AbstractClause extends Collection implements Stringable {

    protected string $clause;
    protected string $separator = ",";

    protected array $items = [];

    public function __construct(protected Builder $query) {
    }

    public function getClause(): string {
        return $this->clause;
    }

    public function getSeparator(): string {
        return $this->separator;
    }

    public function getItems(): array {
        return $this->items;
    }

    public function __toString(): string {
        $items = $this->getItems();
        if (empty($items)) {
            return "";
        }

        $value = $this->query::arrayToString($items, "{$this->getSeparator()} ");

        return "{$this->getClause()} $value";
    }
}

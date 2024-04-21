<?php

declare(strict_types=1);

namespace JPI\Database\Query\Result;

use ArrayIterator;
use JPI\Database\Query\ResultInterface;

class Row implements ResultInterface {

    public function __construct(
        protected array $data
    ) {
    }

    public static function loadFromDatabaseRow(array $row): static {
        return new static($row);
    }

    public function getValue(string $key): mixed {
        return $this->data[$key] ?? null;
    }

    public function toArray(): array {
        return $this->data;
    }

    public function getIterator(): ArrayIterator {
        return new ArrayIterator($this->toArray());
    }
}

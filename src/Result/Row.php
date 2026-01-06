<?php

declare(strict_types=1);

namespace JPI\Database\Query\Result;

use ArrayAccess;
use ArrayIterator;
use Countable;
use JPI\Database\Query\ResultInterface;
use JsonSerializable;
use OutOfBoundsException;

class Row implements ArrayAccess, Countable, JsonSerializable, ResultInterface {

    public function __construct(
        protected array $data
    ) {
    }

    public static function loadFromDatabaseRow(array $row): static {
        return new static($row);
    }

    protected function checkKey(string $key): void {
        if (!array_key_exists($key, $this->data)) {
            throw new OutOfBoundsException("`$key` does not exist in the row.");
        }
    }

    /**
     * @throws OutOfBoundsException
     */
    public function getValue(string $key): mixed {
        $this->checkKey($key);
        return $this->data[$key] ?? null;
    }

    public function offsetExists(mixed $key): bool {
        return array_key_exists($key, $this->data);
    }

    public function offsetGet(mixed $key): mixed {
        return $this->getValue($key);
    }

    public function offsetSet(mixed $key, mixed $value): void {
        $this->checkKey($key);
        $this->data[$key] = $value;
    }

    public function offsetUnset(mixed $key): void {
        unset($this->data[$key]);
    }

    public function toArray(): array {
        return $this->data;
    }

    public function count(): int {
        return count($this->toArray());
    }

    public function getIterator(): ArrayIterator {
        return new ArrayIterator($this->toArray());
    }

    public function jsonSerialize(): array {
        return $this->data;
    }
}

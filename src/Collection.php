<?php

/**
 * Collection to store result from DB queries.
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @version v1.0.0
 * @copyright 2010-2021 JPI
 */

namespace JPI\Database;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

class Collection implements ArrayAccess, Countable, IteratorAggregate {

    /**
     * @var array
     */
    protected $rows;

    /**
     * @var int|null
     */
    protected $count = null;

    /**
     * @var int|null
     */
    protected $totalCount;

    /**
     * @var int|null
     */
    protected $limit;

    /**
     * @var int|null
     */
    protected $page;

    /**
     * @param $rows array
     * @param $totalCount int|null
     * @param $limit int|null
     * @param $page int|null
     */
    public function __construct(array $rows = [], ?int $totalCount = null, ?int $limit = null, ?int $page = null) {
        $this->rows = $rows;
        $this->totalCount = $totalCount ?? null;
        $this->limit = $limit;
        $this->page = $page;
    }

    protected function resetCount(): void {
        $this->count = null;
    }

    /**
     * @param $key int
     * @param $row array
     */
    public function set(int $key, array $row): void {
        $this->rows[$key] = $row;
        $this->resetCount();
    }

    /**
     * @param $row array
     */
    public function add(array $row): void {
        $this->rows[] = $row;
        $this->resetCount();
    }

    /**
     * @param $key string
     */
    public function removeByKey(string $key): void {
        unset($this->rows[$key]);
        $this->resetCount();
    }

    /**
     * @param $key string
     * @return bool
     */
    protected function doesKeyExist(string $key): bool {
        return array_key_exists($key, $this->rows);
    }

    /**
     * @param $key string
     * @return array|null
     */
    public function get(string $key): ?array {
        return $this->rows[$key] ?? null;
    }

    // ArrayAccess //

    /**
     * @param $offset string
     * @return bool
     */
    public function offsetExists($key): bool {
        return $this->doesKeyExist($key);
    }

    /**
     * @param $offset string
     * @return array|null
     */
    public function offsetGet($key): ?array {
        return $this->get($key);
    }

    /**
     * @param $offset string
     * @param $item array
     */
    public function offsetSet($key, $row): void {
        if ($key === null) {
            $this->add($row);
        }
        else {
            $this->set($key, $row);
        }
    }

    /**
     * @param $offset string
     */
    public function offsetUnset($key): void {
        $this->removeByKey($key);
    }

    // IteratorAggregate //

    /**
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator {
        return new ArrayIterator($this->rows);
    }

    // Countable //

    /**
     * @return int
     */
    public function count(): int {
        if ($this->count === null) {
            $this->count = count($this->rows);
        }

        return $this->count;
    }

    /**
     * @return int
     */
    public function getCount(): int {
        return $this->count();
    }

    /**
     * @return int
     */
    public function getTotalCount(): int {
        return $this->totalCount ?? $this->count();
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int {
        return $this->limit;
    }

    /**
     * @return int|null
     */
    public function getPage(): ?int {
        return $this->page;
    }

    /**
     * @param $row array
     * @param $column string
     * @param $default mixed
     * @return string|int|float|null
     */
    protected static function getFromRow(array $row, string $column, $default = null) {
        return $row[$column] ?? $default;
    }

    /**
     * @param $columnToPluck string
     * @param $keyedByColumn string|null
     * @return array
     */
    public function pluck(string $columnToPluck, string $keyedByColumn = null): array {
        $plucked = [];

        foreach ($this as $row) {
            $value = static::getFromRow($row, $columnToPluck);

            if ($keyedByColumn) {
                $keyValue = static::getFromRow($row, $keyedByColumn);
                $plucked[$keyValue] = $value;
            }
            else {
                $plucked[] = $value;
            }
        }

        return $plucked;
    }

    /**
     * @param $column string
     * @return Collection
     */
    public function groupBy(string $column): Collection {
        $collection = new static();

        foreach ($this as $row) {
            $value = static::getFromRow($row, $column);

            if (!isset($collection[$value])) {
                $collection[$value] = new static([$row]);
            }
            else {
                $collection[$value][] = $row;
            }
        }

        return $collection;
    }
}

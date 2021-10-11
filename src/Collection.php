<?php

/**
 * Collection to store result from DB queries.
 *
 * PHP version 7.1+
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
    protected $items;

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
     * @param $items array
     * @param $totalCount int|null
     * @param $limit int|null
     * @param $page int|null
     */
    public function __construct(array $items = [], ?int $totalCount = null, ?int $limit = null, ?int $page = null) {
        $this->items = $items;
        $this->totalCount = $totalCount ?? null;
        $this->limit = $limit;
        $this->page = $page;
    }

    protected function resetCount(): void {
        $this->count = null;
    }

    /**
     * @param $key string
     * @param $item array
     */
    public function set(string $key, array $item): void {
        $this->items[$key] = $item;
        $this->resetCount();
    }

    /**
     * @param $item array
     */
    public function add(array $item): void {
        $this->items[] = $item;
        $this->resetCount();
    }

    /**
     * @param $key string
     */
    public function removeByKey(string $key): void {
        unset($this->items[$key]);
        $this->resetCount();
    }

    /**
     * @param $key string
     * @return bool
     */
    protected function doesKeyExist(string $key): bool {
        return array_key_exists($key, $this->items);
    }

    /**
     * @param $key string
     * @param $default mixed|null
     * @return array|null
     */
    public function get(string $key, $default = null): array {
        return $this->items[$key] ?? $default;
    }

    // ArrayAccess //

    /**
     * @param $offset string
     * @return bool
     */
    public function offsetExists($offset): bool {
        return $this->doesKeyExist($offset);
    }

    /**
     * @param $offset string
     * @return array|null
     */
    public function offsetGet($offset): ?array {
        return $this->get($offset);
    }

    /**
     * @param $offset string
     * @param $item array
     */
    public function offsetSet($offset, $item): void {
        if ($offset === null) {
            $this->add($item);
        }
        else {
            $this->set($offset, $item);
        }
    }

    /**
     * @param $offset string
     */
    public function offsetUnset($offset): void {
        $this->removeByKey($offset);
    }

    // IteratorAggregate //

    /**
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator {
        return new ArrayIterator($this->items);
    }

    // Countable //

    /**
     * @return int
     */
    public function count(): int {
        if ($this->count === null) {
            $this->count = count($this->items);
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
     * @param $item array
     * @param $key string
     * @param $default mixed
     * @return string|int|float|null
     */
    protected static function getFromItem(array $item, string $key, $default = null) {
        return $item[$key] ?? $default;
    }

    /**
     * @param $keyToPluck string
     * @param $keyedBy string|null
     * @return array
     */
    public function pluck(string $keyToPluck, string $keyedBy = null): array {
        $plucked = [];

        foreach ($this as $item) {
            $value = static::getFromItem($item, $keyToPluck);

            if ($keyedBy) {
                $keyValue = static::getFromItem($item, $keyedBy);
                $plucked[$keyValue] = $value;
            }
            else {
                $plucked[] = $value;
            }
        }

        return $plucked;
    }

    /**
     * @param $key string
     * @return Collection
     */
    public function groupBy(string $key): Collection {
        $collection = new static();

        foreach ($this as $item) {
            $value = static::getFromItem($item, $key);

            if (!isset($collection[$value])) {
                $collection[$value] = new static([$item]);
            }
            else {
                $collection[$value][] = $item;
            }
        }

        return $collection;
    }
}

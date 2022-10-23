<?php

namespace JPI\Database;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * Collection to store result from DB queries.
 *
 * @author Jahidul Pabel Islam <me@jahidulpabelislam.com>
 * @copyright 2012-2022 JPI
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate {

    /**
     * @var array
     */
    protected $rows;

    /**
     * @var int
     */
    protected $count;

    /**
     * @var int
     */
    protected $totalCount;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $page;

    /**
     * @param array $rows
     * @param int $totalCount
     * @param int $limit
     * @param int $page
     */
    public function __construct(array $rows, int $totalCount, int $limit, int $page) {
        $this->rows = $rows;
        $this->count = count($this->rows);
        $this->totalCount = $totalCount;
        $this->limit = $limit;
        $this->page = $page;
    }

    /**
     * @param int $key
     * @return bool
     */
    public function isset(int $key): bool {
        return array_key_exists($key, $this->rows);
    }

    /**
     * @param int $key
     * @return array|null
     */
    public function get(int $key): ?array {
        return $this->rows[$key] ?? null;
    }

    // ArrayAccess //

    /**
     * @param int $key
     * @return bool
     */
    public function offsetExists($key): bool {
        return $this->isset($key);
    }

    /**
     * @param int $key
     * @return array|null
     */
    public function offsetGet($key): ?array {
        return $this->get($key);
    }

    /**
     * @param int $key
     * @param array $row
     * @return void
     * @throws Exception
     */
    public function offsetSet($key, $row): void {
        throw new Exception("Updating is not allowed");
    }

    /**
     * @param string $key
     * @return void
     * @throws Exception
     */
    public function offsetUnset($key): void {
        throw new Exception("Updating is not allowed");
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
        return $this->totalCount;
    }

    /**
     * @return int
     */
    public function getLimit(): int {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getPage(): int {
        return $this->page;
    }
}

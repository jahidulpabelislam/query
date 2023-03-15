<?php

declare(strict_types=1);

namespace JPI\Database\Query\Where;

use JPI\Database\Query\Builder;
use JPI\Database\Query\ParamableInterface;
use JPI\Database\Query\WhereableInterface;
use JPI\Database\Query\WhereableTrait;

abstract class Condition implements WhereableInterface, ParamableInterface {

    use WhereableTrait;

    protected $condition;

    public function __construct(Builder $query) {
        $this->query = $query;
    }

    /**
     * @param $key string
     * @param $value string|int|float
     * @return $this
     */
    public function param(string $key, $value) {
        $this->query->param($key, $value);
        return $this;
    }

    public function params(array $params) {
        $this->query->params($params);
        return $this;
    }

    public function getCondition(): string {
        return $this->condition;
    }

    public function __toString() {
        $count = count($this->wheres);
        if (!$count) {
            return "";
        }

        $clause = $this->query::arrayToString($this->wheres, " {$this->getCondition()} ");

        if ($count > 1) {
            return "($clause)";
        }

        return $clause;
    }
}

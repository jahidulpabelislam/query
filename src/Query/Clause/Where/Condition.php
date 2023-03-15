<?php

declare(strict_types=1);

namespace JPI\Database\Query\Clause\Where;

use JPI\Database\Query\Builder;
use JPI\Database\Query\ParamableInterface;
use JPI\Database\Query\WhereableInterface;
use JPI\Database\Query\WhereableTrait;
use Stringable;

abstract class Condition implements WhereableInterface, ParamableInterface, Stringable {

    use WhereableTrait;

    protected string $condition;

    public function __construct(protected Builder $query) {
    }

    public function param(string $key, string|int|float $value): ParamableInterface {
        $this->query->param($key, $value);
        return $this;
    }

    public function params(array $params): ParamableInterface {
        $this->query->params($params);
        return $this;
    }

    public function getCondition(): string {
        return $this->condition;
    }

    public function __toString(): string {
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

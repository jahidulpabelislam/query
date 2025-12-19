<?php

declare(strict_types=1);

namespace JPI\Database\Query\Clause\Where;

use JPI\Database\Query\Builder;
use JPI\Database\Query\DelegatedParamableTrait;
use JPI\Database\Query\ParamableInterface;
use JPI\Database\Query\WhereableInterface;
use JPI\Database\Query\WhereableTrait;
use Stringable;

abstract class Condition implements WhereableInterface, ParamableInterface, Stringable {

    use DelegatedParamableTrait;
    use WhereableTrait;

    protected string $condition;

    public function __construct(protected Builder $query) {
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

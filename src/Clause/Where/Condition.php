<?php

declare(strict_types=1);

namespace JPI\Database\Query\Clause\Where;

use JPI\Database\Query\Builder;
use JPI\Database\Query\DelegatedParamableTrait;
use JPI\Database\Query\ParamableInterface;
use JPI\Database\Query\WhereableInterface;
use JPI\Database\Query\WhereableTrait;
use JPI\Utils\Collection;
use Stringable;

abstract class Condition extends Collection implements WhereableInterface, ParamableInterface, Stringable {

    use DelegatedParamableTrait;
    use WhereableTrait;

    protected string $operator;

    public function __construct(protected Builder $query) {
        parent::__construct();
    }

    public function getOperator(): string {
        return $this->operator;
    }

    public function __toString(): string {
        $count = count($this);
        if (!$count) {
            return "";
        }

        $clause = $this->query::arrayToString($this, " {$this->getOperator()} ");

        if ($count > 1) {
            return "($clause)";
        }

        return $clause;
    }
}

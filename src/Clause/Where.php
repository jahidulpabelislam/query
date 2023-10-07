<?php

declare(strict_types=1);

namespace JPI\Database\Query\Clause;

use JPI\Database\Query\Clause\Where\AndCondition;

class Where extends AndCondition {

    public function __toString(): string {
        if (!count($this->wheres)) {
            return "";
        }

        return "WHERE " . $this->query::arrayToString($this->wheres, " {$this->getCondition()} ");
    }
}

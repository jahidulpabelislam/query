<?php

namespace JPI\Database\Query\Clause;

use JPI\Database\Query\Clause\Where\AndCondition;

class Where extends AndCondition {

    public function __toString(): string {
        $string = parent::__toString();

        if (!$string) {
            return "";
        }

        return "WHERE $string";
    }
}

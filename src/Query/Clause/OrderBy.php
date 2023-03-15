<?php

declare(strict_types=1);

namespace JPI\Database\Query\Clause;

use JPI\Database\Query\AbstractClause;

class OrderBy extends AbstractClause {

    protected string $clause = "ORDER BY";
}

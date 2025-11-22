<?php

declare(strict_types=1);

namespace JPI\Database\Query\Clause;

use JPI\Database\Query\AbstractClause;
use JPI\Database\Query\WhereableTrait;

class Join extends AbstractClause {

    use WhereableTrait;

    protected string $clause = "JOIN";

    public function on(
        string $onOrColumn,
        ?string $expression = null,
        string|int|float|array $valueOrPlaceholder = null
    ): static {
        $this->where($onOrColumn, $expression, $valueOrPlaceholder);
        return $this;
    }

}


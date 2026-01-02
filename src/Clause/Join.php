<?php

declare(strict_types=1);

namespace JPI\Database\Query\Clause;

use JPI\Database\Query\AbstractClause;
use JPI\Database\Query\Builder;
use JPI\Database\Query\DelegatedParamableTrait;
use JPI\Database\Query\WhereableTrait;
use Stringable;

class Join extends AbstractClause {

    use DelegatedParamableTrait;
    use WhereableTrait;

    protected string $clause = "JOIN";
    protected string $separator = " AND";

    public function __construct(
        protected Builder $query,
        protected string $table,
        string $type = "INNER",
        ?string $on = null
    ) {
        parent::__construct($query);

        $this->clause = strtoupper($type) . " $this->clause";

        if ($on) {
            $this->on($on);
        }
    }

    public function on(
        Stringable|string $columnOrExpression,
        ?string $operator = null,
        Stringable|string|int|float|array|null $valueOrPlaceholder = null
    ): static {
        $this->where($columnOrExpression, $operator, $valueOrPlaceholder);
        return $this;
    }

    public function getClause(): string {
        return "$this->clause $this->table ON";
    }
}

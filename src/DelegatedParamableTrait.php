<?php

declare(strict_types=1);

namespace JPI\Database\Query;

/**
 * Delegate the param methods to the query builder.
 */
trait DelegatedParamableTrait {

    public function param(string $key, string|int|float $value): static {
        $this->query->param($key, $value);
        return $this;
    }

    public function params(array $params): static {
        $this->query->params($params);
        return $this;
    }
}

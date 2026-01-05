<?php

declare(strict_types=1);

namespace JPI\Database\Query;

use Stringable;

trait ParamableTrait {

    protected array $params = [];

    public function param(string $key, Stringable|string|int|float|null $value): static {
        $this->params[$key] = $value;
        return $this;
    }

    public function params(array $params): static {
        $this->params = array_merge($this->params, $params);
        return $this;
    }
}

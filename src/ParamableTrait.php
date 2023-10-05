<?php

declare(strict_types=1);

namespace JPI\Database\Query;

trait ParamableTrait {

    protected array $params = [];

    public function param(string $key, string|int|float $value): static {
        $this->params[$key] = $value;
        return $this;
    }

    public function params(array $params): static {
        $this->params = array_merge($this->params, $params);
        return $this;
    }
}

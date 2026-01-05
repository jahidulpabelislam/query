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
        // Run through each param and set it - to allow for any processing in param()
        foreach ($params as $key => $value) {
            $this->param($key, $value);
        }

        return $this;
    }

    public function getParams(): array {
        return $this->params;
    }
}

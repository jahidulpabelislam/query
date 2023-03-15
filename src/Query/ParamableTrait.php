<?php

declare(strict_types=1);

namespace JPI\Database\Query;

trait ParamableTrait {

    protected array $params = [];

    public function param(string $key, string|int|float $value): ParamableInterface {
        $this->params[$key] = $value;
        return $this;
    }

    public function params(array $params): ParamableInterface {
        $this->params = array_merge($this->params, $params);
        return $this;
    }
}

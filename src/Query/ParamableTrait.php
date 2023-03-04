<?php

declare(strict_types=1);

namespace JPI\Database\Query;

trait ParamableTrait {

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @param $key string
     * @param $value string|int|float
     * @return $this
     */
    public function param(string $key, $value) {
        $this->params[$key] = $value;
        return $this;
    }

    public function params(array $params) {
        $this->params = array_merge($this->params, $params);
        return $this;
    }
}

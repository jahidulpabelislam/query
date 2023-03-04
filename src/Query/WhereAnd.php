<?php

declare(strict_types=1);

namespace JPI\Database\Query;

class WhereAnd implements WhereableInterface, ParamableInterface {

    use WhereableTrait;

    protected $separator = "AND";

    public function __construct(Builder $query) {
        $this->query = $query;
    }

    /**
     * @param $key string
     * @param $value string|int|float
     * @return $this
     */
    public function param(string $key, $value) {
        $this->query->param($key, $value);
        return $this;
    }

    public function params(array $params) {
        $this->query->params($params);
        return $this;
    }

    public function __toString() {
        return "(" . Generator::arrayToString($this->wheres, " $this->separator ") . ")";
    }
}

<?php

declare(strict_types=1);

namespace JPI\Database\Query;

interface ParamableInterface {

    /**
     * @param $key string
     * @param $value string|int|float
     * @return $this
     */
    public function param(string $key, $value);

    public function params(array $params);
}

<?php

declare(strict_types=1);

namespace JPI\Database\Query;

interface ParamableInterface {

    public function param(string $key, string|int|float $value): static;

    public function params(array $params): static;
}

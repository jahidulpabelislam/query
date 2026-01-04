<?php

declare(strict_types=1);

namespace JPI\Database\Query;

use Stringable;

interface ParamableInterface {

    public function param(string $key, Stringable|string|int|float $value): static;

    public function params(array $params): static;

    public function getParams(): array;
}

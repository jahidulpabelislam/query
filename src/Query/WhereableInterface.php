<?php

declare(strict_types=1);

namespace JPI\Database\Query;

interface WhereableInterface {

    public function where(string $whereOrColumn, ?string $expression = null, string|int|float|array $valueOrPlaceholder = null): WhereableInterface;
}

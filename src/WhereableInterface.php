<?php

declare(strict_types=1);

namespace JPI\Database\Query;

use Stringable;

interface WhereableInterface {

    public function where(
        Stringable|string $whereOrColumn,
        ?string $operator = null,
        Stringable|string|int|float|array|null $valueOrPlaceholder = null
    ): static;
}

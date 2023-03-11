<?php

declare(strict_types=1);

namespace JPI\Database\Query;

interface WhereableInterface {

    /**
     * @param $whereOrColumn string
     * @param $expression string|null
     * @param $valueOrPlaceholder mixed
     * @return $this
     */
    public function where(string $whereOrColumn, ?string $expression = null, $valueOrPlaceholder = null);
}

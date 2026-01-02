<?php

declare(strict_types=1);

namespace JPI\Database\Query;

use Stringable;

/**
 * Assumes this is used in a class implementing ArrayAccess where the items are the where clauses.
 */
trait WhereableTrait {

    abstract public function param(string $key, Stringable|string|int|float $value): static;

    public function where(
        Stringable|string $whereOrColumn,
        ?string $operator = null,
        Stringable|string|int|float|array|null $valueOrPlaceholder = null
    ): static {
        if ($operator === null && $valueOrPlaceholder === null) {
            $this[] = $whereOrColumn;
            return $this;
        }

        if (is_array($valueOrPlaceholder) && count($valueOrPlaceholder) === 1) {
            $operator = $operator === "NOT IN" ? "<>" : "=";
            $valueOrPlaceholder = reset($valueOrPlaceholder);
        }

        if (is_array($valueOrPlaceholder)) {
            $operator = $operator ?: "IN";
            $ins = [];
            foreach ($valueOrPlaceholder as $i => $value) {
                $key = "{$whereOrColumn}_" . ($i + 1);
                $ins[] = ":$key";
                $this->param($key, $value);
            }
            $placeholder = "(" . implode(", ", $ins) . ")";
        }
        else if ($valueOrPlaceholder !== null && (!is_string($valueOrPlaceholder) || $valueOrPlaceholder[0] !== ":")) {
            $placeholder = ":$whereOrColumn";
            $this->param($whereOrColumn, $valueOrPlaceholder);
        }
        else {
            $placeholder = $valueOrPlaceholder;
        }

        $this[] = trim("$whereOrColumn $operator $placeholder", " ");
        return $this;
    }
}

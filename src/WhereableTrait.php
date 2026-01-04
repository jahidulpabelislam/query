<?php

declare(strict_types=1);

namespace JPI\Database\Query;

use Stringable;

/**
 * Assumes this is used in a class implementing ArrayAccess where the items are the expressions.
 */
trait WhereableTrait {

    abstract public function param(string $key, Stringable|string|int|float $value): static;

    public function where(
        Stringable|string $columnOrExpression,
        ?string $operator = null,
        Stringable|string|int|float|array|null $valueOrPlaceholder = null
    ): static {
        if ($operator === null && $valueOrPlaceholder === null) {
            $this[] = $columnOrExpression;
            return $this;
        }

        if ($operator === "BETWEEN") {
            $this->param("{$columnOrExpression}_1", $valueOrPlaceholder[0]);
            $this->param("{$columnOrExpression}_2", $valueOrPlaceholder[1]);
            $this[] = "$columnOrExpression BETWEEN :{$columnOrExpression}_1 AND :{$columnOrExpression}_2";
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
                $key = "{$columnOrExpression}_" . ($i + 1);
                $ins[] = ":$key";
                $this->param($key, $value);
            }
            $placeholder = "(" . implode(", ", $ins) . ")";
        }
        else if ($valueOrPlaceholder !== null && (!is_string($valueOrPlaceholder) || $valueOrPlaceholder[0] !== ":")) {
            $placeholder = ":$columnOrExpression";
            $this->param($columnOrExpression, $valueOrPlaceholder);
        }
        else {
            $placeholder = $valueOrPlaceholder;
        }

        $this[] = trim("$columnOrExpression $operator $placeholder", " ");
        return $this;
    }
}

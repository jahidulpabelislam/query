<?php

declare(strict_types=1);

namespace JPI\Database\Query;

use Stringable;

/**
 * Assumes this is used in a class implementing ArrayAccess where the items are the expressions.
 */
trait WhereableTrait {

    abstract public function param(string $key, Stringable|string|int|float $value): static;

    abstract public function params(array $params): static;

    public function where(
        Stringable|string $columnOrExpression,
        ?string $operator = null,
        Builder|Stringable|string|int|float|array|null $valueOrPlaceholder = null
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

        // If value is a builder, assume we want the SELECT
        if ($valueOrPlaceholder instanceof Builder) {
            $this->params($valueOrPlaceholder->getParams()); // Need to propagate params
            $valueOrPlaceholder = "(" . rtrim($valueOrPlaceholder->getSelectQuery(), ";") . ")";
        }
        else if (is_array($valueOrPlaceholder)) {
            $operator = $operator ?: "IN";
            $ins = [];
            foreach ($valueOrPlaceholder as $i => $value) {
                $key = "{$columnOrExpression}_" . ($i + 1);
                $ins[] = ":$key";
                $this->param($key, $value);
            }
            $valueOrPlaceholder = "(" . implode(", ", $ins) . ")";
        }
        else if (
            $valueOrPlaceholder !== null
            && (
                !is_string($valueOrPlaceholder)
                || !isset($valueOrPlaceholder[0])
                || $valueOrPlaceholder[0] !== ":"
            )
        ) {
            $this->param($columnOrExpression, $valueOrPlaceholder);
            $valueOrPlaceholder = ":$columnOrExpression";
        }

        $this[] = trim("$columnOrExpression $operator $valueOrPlaceholder", " ");
        return $this;
    }
}

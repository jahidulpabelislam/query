<?php

declare(strict_types=1);

namespace JPI\Database\Query;

trait WhereableTrait {

    protected array $wheres = [];

    abstract public function param(string $key, string|int|float $value): static;

    public function where(
        string $whereOrColumn,
        ?string $expression = null,
        string|int|float|array|null $valueOrPlaceholder = null
    ): static {
        if ($expression === null && $valueOrPlaceholder === null) {
            $this->wheres[] = $whereOrColumn;
            return $this;
        }

        if (is_array($valueOrPlaceholder)) {
            $expression = "IN";
            $ins = [];
            foreach ($valueOrPlaceholder as $i => $value) {
                $key = "{$whereOrColumn}_" . ($i + 1);
                $ins[] = ":$key";
                $this->param($key, $value);
            }
            $placeholder = "(" . implode(", ", $ins) . ")";
        }
        else if (!is_string($valueOrPlaceholder) || $valueOrPlaceholder[0] !== ":") {
            $placeholder = ":$whereOrColumn";
            $this->param($whereOrColumn, $valueOrPlaceholder);
        }
        else {
            $placeholder = $valueOrPlaceholder;
        }

        $this->wheres[] = "$whereOrColumn $expression $placeholder";
        return $this;
    }
}

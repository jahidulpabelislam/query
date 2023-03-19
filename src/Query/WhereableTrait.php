<?php

declare(strict_types=1);

namespace JPI\Database\Query;

trait WhereableTrait {

    protected array $wheres = [];

    public function where(string $whereOrColumn, ?string $expression = null, string|int|float|array $valueOrPlaceholder = null): WhereableInterface {
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

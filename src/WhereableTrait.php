<?php

declare(strict_types=1);

namespace JPI\Database\Query;

trait WhereableTrait {

    protected array $wheres = [];

    protected array $paramCounters = [];

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
            
            // Update counter to account for the parameter names used by IN clause
            $this->paramCounters[$whereOrColumn] = count($valueOrPlaceholder);
        }
        else if (!is_string($valueOrPlaceholder) || $valueOrPlaceholder[0] !== ":") {
            // Generate unique parameter key
            if (isset($this->paramCounters[$whereOrColumn])) {
                $this->paramCounters[$whereOrColumn]++;
                $key = "{$whereOrColumn}_{$this->paramCounters[$whereOrColumn]}";
            }
            else {
                $this->paramCounters[$whereOrColumn] = 0;
                $key = $whereOrColumn;
            }
            
            $placeholder = ":$key";
            $this->param($key, $valueOrPlaceholder);
        }
        else {
            $placeholder = $valueOrPlaceholder;
        }

        $this->wheres[] = "$whereOrColumn $expression $placeholder";
        return $this;
    }
}

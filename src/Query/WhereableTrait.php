<?php

declare(strict_types=1);

namespace JPI\Database\Query;

trait WhereableTrait {

    /**
     * @var array
     */
    protected $wheres = [];

    /**
     * @param $whereOrColumn string
     * @param $expression string|null
     * @param $valueOrPlaceholder mixed
     * @return $this
     */
    public function where(string $whereOrColumn, ?string $expression = null, $valueOrPlaceholder = null) {
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
        } else {
            if (is_string($valueOrPlaceholder) && $valueOrPlaceholder[0] !== ":") {
                $placeholder = ":$valueOrPlaceholder";
                $this->param($valueOrPlaceholder, $valueOrPlaceholder);
            } else {
                $placeholder = $valueOrPlaceholder;
            }
        }

        $this->wheres[] = "$whereOrColumn $expression $placeholder";
        return $this;
    }
}

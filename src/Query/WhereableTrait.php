<?php

declare(strict_types=1);

namespace JPI\Database\Query;

trait WhereableTrait {

    /**
     * @var array
     */
    protected $wheres = [];

    /**
     * @param $where string|int
     * @return $this
     */
    public function where($where) {
        $expression = "=";

        $args = func_get_args();

        // params = int
        if (!isset($args[1]) && is_numeric($where)) {
            // (column, expression, value)
            $args = ["id", "=", (int)$where];
        }

        // params = column, expression, value
        if (isset($args[2])) {
            [$where, $expression, $value] = $args;
        }

        // params = (column, expression, value) OR (column, value)
        if (isset($args[1])) {
            $value = $value ?? $args[1];

            if (is_array($value)) {
                $expression = 'IN';

                $values = $value;
                $ins = [];
                foreach ($values as $i => $value) {
                    $key = "{$where}_" . ($i + 1);
                    $ins[] = ":$key";
                    $this->param($key, $value);
                }
                $placeholder = "(" . implode(", ", $ins) . ")";
            } else {
                if (is_string($value) && $value[0] !== ':') {
                    $placeholder = ":$where";
                    $this->param($where, $value);
                } else {
                    $placeholder = $value;
                }
            }

            $where = "$where $expression $placeholder";
        }

        $this->wheres[] = (string) $where;
        return $this;
    }
}

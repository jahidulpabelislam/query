<?php

namespace JPI\Database;

class Utilities {

    /**
     * Try and force value as an array if not already
     *
     * @param $value array|string|null
     * @return array
     */
    public static function initArray($value): array {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            return [$value];
        }

        return [];
    }

    /**
     * Convenient function to pluck/get out the single value from an array if it's the only value.
     * Then build a string value if an array.
     *
     * @param $value string[]|string|null
     * @param $separator string
     * @return string
     */
    public static function arrayToQueryString($value, string $separator = ",\n\t"): string {
        if ($value && is_array($value) && count($value) === 1) {
            $value = array_shift($value);
        }

        if (is_array($value) && count($value)) {
            $value = "\n\t" . implode($separator, $value);
        }

        if (!$value || !is_string($value)) {
            return "";
        }

        return $value;
    }

    /**
     * @param $parts array
     * @return string
     */
    public static function buildQuery(array $parts): string {
        $query = implode("\n", $parts);
        $query .= ";";

        return $query;
    }

}

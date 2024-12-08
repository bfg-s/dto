<?php

if (! function_exists('enum_value')) {
    /**
     * Return a scalar value for the given value that might be an enum.
     *
     * @param  TValue  $value
     * @param  callable(TValue): TDefault|null  $default
     * @return ($value is empty ? TDefault : mixed)
     *
     * @template TValue
     * @template TDefault
     *
     */
    function enum_value($value, callable $default = null)
    {
        return transform($value, fn ($value) => match (true) {
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \UnitEnum => $value->name,

            default => $value,
        }, $default ?? $value);
    }
}

if (! function_exists('is_assoc')) {
    /**
     * Determine if the given value is an associative array.
     *
     * @param  array  $array
     * @return bool
     */
    function is_assoc(array $array): bool
    {
        if ([] === $array) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}

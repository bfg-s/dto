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

<?php

use Bfg\Dto\Dto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

if (!function_exists('enum_value')) {
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
        return transform($value, fn($value) => match (true) {
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \UnitEnum => $value->name,

            default => $value,
        }, $default ?? $value);
    }
}

if (!function_exists('is_assoc')) {
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

if (!function_exists('dto_string_replace')) {
    /**
     * Invested tag replacement on the object values or array.
     *
     * @template T as array|string
     * @param  T  $text
     * @param  array|object  $materials
     * @param  string  $pattern
     * @return T|null
     */
    function dto_string_replace(array|string $text, array|object $materials, string $pattern = "{*}"): array|string|null
    {
        $pattern = preg_quote($pattern);
        $pattern = str_replace('\*', '([a-zA-Z0-9\_\-\.]+?)([?]?)', $pattern);

        return preg_replace_callback("/{$pattern}/", function ($m) use ($materials) {
            $data = data_get($materials, $m[1]);
            if ($data instanceof \BackedEnum) {
                $data = $data->value;
            } elseif ($data instanceof \UnitEnum) {
                $data = $data->name;
            } elseif ($data instanceof Carbon\Carbon) {
                $data = $data->toIso8601String();
            } elseif ($data instanceof Model) {
                $data = $data->getKey();
            } elseif ($data instanceof Collection || $data instanceof Dto) {
                $data = $data->toJson(JSON_UNESCAPED_UNICODE);
            }
            return $data !== null ? (is_array($data) ? json_encode($data) : $data) : '';
        }, $text);
    }
}

<?php

use Bfg\Dto\Dto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

define("DTO_DATA_EXISTS_IGNORE_VALUE_COMPARE", sha1('__IGNORE_VALUE_COMPARE__'));
define("DTO_DATA_EXISTS_NOT_FOUND_FLAG", sha1('__NOT_FOUND_FLAG__'));

if (!function_exists('dto_data_exists')) {
    /**
     * Check if a key exists in an array or object, with optional type and value comparison.
     *
     * @param  array|object  $target
     * @param  string  $key
     * @param  string  $type
     * @param  mixed  $needValue
     * @return bool
     */
    function dto_data_exists(
        array|object $target,
        string $key,
        string $type = 'mixed',
        mixed $needValue = DTO_DATA_EXISTS_IGNORE_VALUE_COMPARE
    ): bool {
        $target = $target instanceof Arrayable ? $target->toArray() : $target;
        $target = is_object($target) ? get_object_vars($target) : $target;
        $targetDot = Arr::dot($target);
        $types = Str::of($type)->lower()->replace(['.',',',';'], '|')->explode('|');
        $trues = [];

        foreach ($targetDot as $targetKey => $targetValue) {
            if (
                Str::is($key, $targetKey)
                && ($needValue === DTO_DATA_EXISTS_IGNORE_VALUE_COMPARE
                    || json_encode($targetValue) === json_encode($needValue))
            ) {
                $trues[] = $types->contains('mixed')
                    || $types->contains(get_debug_type($targetValue));
            }
        }

        if (count($trues) === 0) {
            $targetValue = data_get($target, $key, DTO_DATA_EXISTS_NOT_FOUND_FLAG);
            if ($targetValue !== DTO_DATA_EXISTS_NOT_FOUND_FLAG) {
                $trues[] = $types->contains('mixed')
                    || $types->contains(get_debug_type($targetValue))
                    || ($needValue === DTO_DATA_EXISTS_IGNORE_VALUE_COMPARE
                        || json_encode($targetValue) === json_encode($needValue));
            }
        }

        return count($trues) > 0 && ! in_array(false, $trues, true);
    }
}

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
    function enum_value($value, callable|null $default = null)
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

if (! function_exists('dto_to_string')) {
    /**
     * Convert a value to a string representation.
     *
     * @param  mixed  $value
     * @param  string  $default
     * @return string
     */
    function dto_to_string(mixed $value, string $default = ''): string
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }
        $value = is_callable($value) ? call_user_func($value) : $value;
        return is_scalar($value)
            ? (string) $value
            : (is_array($value) || is_object($value)
                ? (json_encode($value, JSON_FORCE_OBJECT|JSON_UNESCAPED_UNICODE) ?: $default)
                : $default);
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

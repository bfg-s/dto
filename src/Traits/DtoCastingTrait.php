<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Attributes\DtoCast;
use Bfg\Dto\Exceptions\DtoInvalidCastException;
use Bfg\Dto\Interfaces\CastsInboundAttributes;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException as BrickMathException;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Exceptions\MathException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use InvalidArgumentException;

trait DtoCastingTrait
{
    /**
     * Cast an attribute to a native PHP type.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @param  string|null  $cast
     * @return mixed
     */
    protected static function castAttribute(string $key, mixed $value, array $attributes = [], string $cast = null): mixed
    {
        if (is_object($value) || is_array($value)) {

            return $value;
        }
        $castType = $cast ?: static::getCastType($key);

        if (!$castType) {
            $castType = get_debug_type($value);
        }

        if (is_null($value) && in_array($castType, static::$__primitiveCastTypes)) {
            return null;
        }

        if (static::isEncryptedCastable($key)) {
            $value = static::fromEncryptedString($value);

            $castType = Str::after($castType, 'encrypted:');
        }

        switch ($castType) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return static::fromFloat($value);
            case 'decimal':
                return static::asDecimal($value, (int) explode(':', static::$dtoCast[$key], 2)[1]);
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return static::fromJsonConvert($value, true);
            case 'array':
            case 'json':
                return static::fromJsonConvert($value);
            case 'collection':
                return new BaseCollection(static::fromJsonConvert($value));
            case 'date':
                return static::asDate($value);
            case 'datetime':
            case 'custom_datetime':
                return static::asDateTime($value);
            case 'immutable_date':
                return static::asDate($value)->toImmutable();
            case 'immutable_custom_datetime':
            case 'immutable_datetime':
                return static::asDateTime($value)->toImmutable();
            case 'timestamp':
                return static::asTimestamp($value);
        }

        if (static::isEnumCastable($key)) {
            return static::getEnumCastableAttributeValue($key, $value);
        }

        if (static::isClassCastable($key)) {
            return static::getClassCastableAttributeValue($key, $value, $attributes);
        }

        return $value;
    }

    /**
     * Set the value of a class castable attribute.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return array
     */
    protected static function setClassCastableAttribute(string $key, mixed $value, array $attributes = []): mixed
    {
        $caster = static::resolveCasterClass($key);

        $attributes = array_replace(
            $attributes,
            static::normalizeCastClassResponse($key, $caster->set(
                $key,
                $value,
                $attributes
            ))
        );

        return $attributes[$key];
    }

    /**
     * Normalize the response from a custom class caster.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return array
     */
    protected static function normalizeCastClassResponse(string $key, mixed $value): array
    {
        return is_array($value) ? $value : [$key => $value];
    }

    /**
     * Set the value of an enum castable attribute.
     *
     * @param  string  $key
     * @param  \UnitEnum|int|string|null  $value
     * @return mixed
     */
    protected static function setEnumCastableAttribute(string $key, \UnitEnum|int|string|null $value): mixed
    {
        $enumClass = static::$dtoCast[$key];

        if (! isset($value)) {
            return null;
        } elseif (is_object($value)) {
            return static::getStorableEnumValue($enumClass, $value);
        } else {
            return static::getStorableEnumValue(
                $enumClass,
                static::getEnumCaseFromValue($enumClass, $value)
            );
        }
    }

    /**
     * Get an enum case instance from a given class and value.
     *
     * @param  string  $enumClass
     * @param  string|int  $value
     * @return \UnitEnum|\BackedEnum
     */
    protected static function getEnumCaseFromValue($enumClass, $value): \BackedEnum|\UnitEnum
    {
        return is_subclass_of($enumClass, \BackedEnum::class)
            ? $enumClass::from($value)
            : constant($enumClass.'::'.$value);
    }

    /**
     * Get the storable value from the given enum.
     *
     * @param  string  $expectedEnum
     * @param  \BackedEnum|\UnitEnum  $value
     * @return string|int
     */
    protected static function getStorableEnumValue(string $expectedEnum, \BackedEnum|\UnitEnum $value): int|string
    {
        if (! $value instanceof $expectedEnum) {
            throw new \ValueError(sprintf('Value [%s] is not of the expected enum type [%s].', var_export($value, true), $expectedEnum));
        }

        return enum_value($value);
    }

    /**
     * Cast the given attribute using a custom cast class.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    protected static function getClassCastableAttributeValue(string $key, mixed $value, array $attributes = []): mixed
    {
        $caster = static::resolveCasterClass($key);

        return $caster instanceof CastsInboundAttributes
            ? $value
            : $caster->get($key, $value, $attributes);
    }

    /**
     * Resolve the custom caster class for a given key.
     *
     * @param  string  $key
     * @return mixed
     */
    protected static function resolveCasterClass(string $key): mixed
    {
        $castType = static::$dtoCast[$key];

        $arguments = [];

        if (is_string($castType) && str_contains($castType, ':')) {
            $segments = explode(':', $castType, 2);

            $castType = $segments[0];
            $arguments = explode(',', $segments[1]);
        }

        if (is_subclass_of($castType, Castable::class)) {
            $castType = $castType::castUsing($arguments);
        }

        if (is_object($castType)) {
            return $castType;
        }

        return new $castType(...$arguments);
    }

    /**
     * Determine if the given key is cast using a custom class.
     *
     * @param  string  $key
     * @return bool
     *
     * @throws \Illuminate\Database\Eloquent\InvalidCastException
     */
    protected static function isClassCastable(string $key): bool
    {
        $casts = static::$dtoCast;

        if (! array_key_exists($key, $casts)) {
            return false;
        }

        $castType = static::parseCasterClass($casts[$key]);

        if (in_array($castType, static::$__primitiveCastTypes)) {
            return false;
        }

        if (class_exists($castType)) {
            return true;
        }

        throw new DtoInvalidCastException(static::class, $key, $castType);
    }

    /**
     * Parse the given caster class, removing any arguments.
     *
     * @param  string  $class
     * @return string
     */
    protected static function parseCasterClass(string $class): string
    {
        return ! str_contains($class, ':')
            ? $class
            : explode(':', $class, 2)[0];
    }

    /**
     * Cast the given attribute to an enum.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected static function getEnumCastableAttributeValue(string $key, mixed $value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        $castType = static::$dtoCast[$key];

        if ($value instanceof $castType) {
            return $value;
        }

        return static::getEnumCaseFromValue($castType, $value);
    }

    /**
     * Determine if the given key is cast using an enum.
     *
     * @param  string  $key
     * @return bool
     */
    protected static function isEnumCastable(string $key): bool
    {
        $casts = static::$dtoCast;

        if (! array_key_exists($key, $casts)) {
            return false;
        }

        $castType = $casts[$key];

        if (in_array($castType, static::$__primitiveCastTypes)) {
            return false;
        }

        return enum_exists($castType);
    }

    /**
     * Return a timestamp as unix timestamp.
     *
     * @param  mixed  $value
     * @return int
     */
    protected static function asTimestamp(mixed $value): int
    {
        return static::asDateTime($value)->getTimestamp();
    }

    /**
     * Return a timestamp as DateTime object with time set to 00:00:00.
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Carbon
     */
    protected static function asDate(mixed $value): Carbon
    {
        return static::asDateTime($value)->startOfDay();
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Carbon|false
     */
    protected static function asDateTime(mixed $value): false|Carbon
    {
        if ($value instanceof CarbonInterface) {
            return Date::instance($value);
        }
        if ($value instanceof DateTimeInterface) {
            return Date::parse(
                $value->format('Y-m-d H:i:s.u'),
                $value->getTimezone()
            );
        }

        if (is_numeric($value)) {
            return Date::createFromTimestamp($value, date_default_timezone_get());
        }

        if (static::isStandardDateFormat($value)) {
            return Date::instance(Carbon::createFromFormat('Y-m-d', $value)->startOfDay());
        }

        $format = static::$dtoDateFormat;

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        try {
            $date = Date::createFromFormat($format, $value);
        } catch (InvalidArgumentException) {
            $date = false;
        }

        return $date ?: Date::parse($value);
    }

    /**
     * Determine if the given value is a standard date format.
     *
     * @param  string  $value
     * @return bool
     */
    protected static function isStandardDateFormat(string $value): bool
    {
        return preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value);
    }

    /**
     * Decode the given JSON back into an array or object.
     *
     * @param  string|null  $value
     * @param  bool  $asObject
     * @return mixed
     */
    protected static function fromJsonConvert(?string $value, bool $asObject = false): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value === '[]' || $value === '{}' || $value === '') {
            return $asObject ? new \stdClass() : [];
        }

        return Json::decode($value, ! $asObject);
    }

    /**
     * Return a decimal as string.
     *
     * @param  float|string  $value
     * @param  int  $decimals
     * @return string
     */
    protected static function asDecimal(float|string $value, int $decimals): string
    {
        try {
            return (string) BigDecimal::of($value)->toScale($decimals, RoundingMode::HALF_UP);
        } catch (BrickMathException $e) {
            throw new MathException('Unable to cast value to a decimal.', previous: $e);
        }
    }

    /**
     * Decode the given float.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected static function fromFloat(mixed $value): mixed
    {
        return match ((string) $value) {
            'Infinity' => INF,
            '-Infinity' => -INF,
            'NaN' => NAN,
            default => (float) $value,
        };
    }

    /**
     * Decrypt the given encrypted string.
     *
     * @param  string  $value
     * @return mixed
     */
    protected static function fromEncryptedString(string $value): mixed
    {
        return static::currentEncrypter()->decrypt($value, false);
    }

    /**
     * Get the current encrypter being used by the model.
     *
     * @return \Illuminate\Contracts\Encryption\Encrypter
     */
    protected static function currentEncrypter(): Encrypter
    {
        return static::$__encrypter ?? Crypt::getFacadeRoot();
    }

    /**
     * Get the type of cast for a model attribute.
     *
     * @param  non-empty-string  $name
     * @return string|null
     */
    protected static function getCastType(string $name): ?string
    {
        /** @var \ReflectionParameter|null $param */
        $param = static::findConstructorParameter($name);

        if ($param !== null) {
            $castAttributes = $param->getAttributes(DtoCast::class);
            if (isset($castAttributes[0])) {
                $castAttribute = $castAttributes[0]->newInstance();
                $castType = $castAttribute->cast;
            }
        }

        if (! isset($castType)) {
            $castType = static::$dtoCast[$name] ?? null;
        }

        if ($castType && static::isCustomDateTimeCast($castType)) {
            $convertedCastType = 'custom_datetime';
        } elseif ($castType && static::isImmutableCustomDateTimeCast($castType)) {
            $convertedCastType = 'immutable_custom_datetime';
        } elseif ($castType && static::isDecimalCast($castType)) {
            $convertedCastType = 'decimal';
        } elseif ($castType && class_exists($castType)) {
            $convertedCastType = $castType;
        } elseif ($castType) {
            $convertedCastType = trim(strtolower($castType));
        }

        return $convertedCastType ?? null;
    }

    /**
     * Determine if the cast type is a decimal cast.
     *
     * @param  string  $cast
     * @return bool
     */
    protected static function isDecimalCast(string $cast): bool
    {
        return str_starts_with($cast, 'decimal:');
    }

    /**
     * Determine if the cast type is an immutable custom date time cast.
     *
     * @param  string  $cast
     * @return bool
     */
    protected static function isImmutableCustomDateTimeCast(string $cast): bool
    {
        return str_starts_with($cast, 'immutable_date:') ||
            str_starts_with($cast, 'immutable_datetime:');
    }

    /**
     * Determine if the cast type is a custom date time cast.
     *
     * @param  string  $cast
     * @return bool
     */
    protected static function isCustomDateTimeCast(string $cast): bool
    {
        return str_starts_with($cast, 'date:') ||
            str_starts_with($cast, 'datetime:');
    }

    /**
     * Determine whether a value is an encrypted castable for inbound manipulation.
     *
     * @param  string  $key
     * @return bool
     */
    protected static function isEncryptedCastable(string $key): bool
    {
        return static::hasCast($key, ['encrypted', 'encrypted:array', 'encrypted:collection', 'encrypted:json', 'encrypted:object']);
    }

    /**
     * Determine whether an attribute should be cast to a native type.
     *
     * @param  string  $key
     * @param  array|string|null  $types
     * @return bool
     */
    protected static function hasCast(string $key, array|string $types = null): bool
    {
        if (array_key_exists($key, static::$dtoCast)) {
            return !$types || in_array(static::getCastType($key), (array) $types, true);
        }

        return false;
    }
}

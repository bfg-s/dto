<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Dto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

trait DtoHelpersTrait
{
    const GET_LENGTH_SERIALIZE = 1;
    const GET_LENGTH_JSON = 2;

    /**
     * Make new instance from args
     *
     * @param ...$args
     * @return static
     */
    public static function new(...$args): static
    {
        return static::fromArray($args);
    }

    /**
     * Get the version of the dto
     *
     * @return string
     */
    public static function version(): string
    {
        return static::$version;
    }

    /**
     * Cache dto
     *
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return $this
     */
    public function cache(\DateTimeInterface|\DateInterval|int|null $ttl = null): static
    {
        Cache::put(static::class, $this->toSerialize(), $ttl);

        return $this;
    }

    /**
     * Clear dto cache
     *
     * @param  string|null  $key
     * @return void
     */
    public static function cacheKeyClear(string $key = null): void
    {
        Cache::forget(static::class . ($key ?: ''));
    }

    /**
     * Cache dto property
     *
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return $this
     */
    public function cacheKey(string $key, \DateTimeInterface|\DateInterval|int|null $ttl = null): static
    {
        Cache::put(static::class . $key, $this->get($key), $ttl);

        return $this;
    }

    /**
     * Get cached dto property
     *
     * @param  string  $key
     * @param  mixed|null  $default
     * @return mixed
     */
    public static function getCachedKey(string $key, mixed $default = null): mixed
    {
        return Cache::get(static::class . $key, $default);
    }

    /**
     * Validate dto data by rules
     *
     * @param  array  $rules
     * @param  array  $messages
     * @return bool
     */
    public function validate(array $rules, array $messages = []): bool
    {
        $validator = validator($this->toArray(), $rules, $messages);

        return $validator->fails();
    }

    /**
     * Restore dto to original state
     *
     * @return $this
     */
    public function restore(): static
    {
        foreach ($this->originals() as $key => $value) {

            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * Get the original data of the dto
     *
     * @return array
     */
    public function originals(): array
    {
        return static::$__originals[static::class][spl_object_id($this)] ?? [];
    }

    /**
     * Comparison of DTO objects
     *
     * @param  \Bfg\Dto\Dto  $dto
     * @return bool
     */
    public function equals(Dto $dto): bool
    {
        return $this->toArray() === $dto->toArray();
    }

    /**
     * Fill the instance with an array of attributes.
     *
     * @param  array  $attributes
     * @return $this
     */
    public function fill(array $attributes): static
    {
        foreach (static::$encrypted as $key) {

            if (array_key_exists($key, $attributes)) {
                try {
                    $attributes[$key]
                        = static::currentEncrypter()->decrypt($attributes[$key]);
                } catch (\Throwable) {}
            }
        }

        foreach (static::getConstructorParameters() as $parameter) {

            $this->set(
                ...static::createNameValueFromProperty($parameter, $attributes)
            );
        }

        foreach (static::$extends as $key => $types) {

            $types = is_array($types) ? $types : explode('|', $types);

            $this->set(
                ...static::createNameValueFromExtendedProperty($key, $types, $attributes)
            );
        }

        return $this;
    }

    /**
     * Get the length of the instance.
     *
     * @param  int  $type
     * @return int
     */
    public function length(int $type = self::GET_LENGTH_SERIALIZE): int
    {
        if ($type === static::GET_LENGTH_JSON) {
            return strlen($this->toJson());
        } else if ($type === static::GET_LENGTH_SERIALIZE) {
            return strlen($this->toSerialize());
        }
        return 0;
    }

    /**
     * Get count of properties.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->getPropertyNames());
    }

    /**
     * Check if property exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return property_exists($this, $key)
            || array_key_exists($key, static::$extends);
    }

    /**
     * Generate hash from instance data.
     *
     * @return string
     */
    public function dataHash(): string
    {
        return md5(static::$version . $this->toJson());
    }

    /**
     * Generate hash from instance.
     *
     * @return string
     */
    public function hash(): string
    {
        return md5(spl_object_id($this) . static::$version . $this->toJson());
    }

    /**
     * Clone the instance.
     *
     * @return $this
     */
    public function clone(): static
    {
        return clone $this;
    }

    /**
     * Get the stringable value of the instance.
     *
     * @param  string  $key
     * @return \Illuminate\Support\Stringable
     */
    public function str(string $key): Stringable
    {
        return Str::of($this->get($key));
    }

    /**
     * Get the collection value of the instance.
     *
     * @param  string  $key
     * @return DtoCollection
     */
    public function collect(string $key): DtoCollection
    {
        return new DtoCollection($this->get($key));
    }

    /**
     * Boolable property value.
     *
     * @param  string  $key
     * @return $this
     */
    public function boolable(string $key): static
    {
        $this->set($key, !!$this->{$key});

        return $this;
    }

    /**
     * Toggle property value bool.
     *
     * @param  string  $key
     * @return $this
     */
    public function toggleBool(string $key): static
    {
        $this->set($key, !$this->{$key});

        return $this;
    }

    /**
     * Increment property value.
     *
     * @param  string  $key
     * @return $this
     */
    public function increment(string $key): static
    {
        $this->set($key, $this->{$key} + 1);

        return $this;
    }

    /**
     * Decrement property value.
     *
     * @param  string  $key
     * @return $this
     */
    public function decrement(string $key): static
    {
        $this->set($key, $this->{$key} - 1);

        return $this;
    }

    /**
     * Set property value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function set(string $key, mixed $value = null): static
    {
        $arguments = $this->vars();
        $value = static::fireEvent(['updating', $key], $value, static::SET_CURRENT_DATA, $this);
        $old = $this->{$key} ?? (static::$__parameters[static::class][spl_object_id($this)][$key] ?? null);
        $isExtended = array_key_exists($key, static::$extends);
        if (! static::$__setWithoutCasting) {
            $value = static::castAttribute($key, $value, $arguments);
        } else {
            static::$__setWithoutCasting = false;
        }
        $setMutatorMethod = 'fromArray' . ucfirst(Str::camel($key));

        if (method_exists(static::class, $setMutatorMethod)) {
            $value = static::fireEvent(['mutating', $key], $value,static::SET_CURRENT_DATA, $this, $arguments);
            $value = $this->{$setMutatorMethod}($value);
            $value = static::fireEvent(['mutated', $key], $value, static::SET_CURRENT_DATA, $this, $arguments);
        }

        if (! $value) {

            try {
                $parameter = new \ReflectionProperty($this, $key);
            } catch (\ReflectionException $e) {
                $parameter = null;
            }
            if ($parameter) {
                $type = $parameter->getType();
                $types = [];
                if ($type instanceof \ReflectionUnionType) {
                    foreach ($type->getTypes() as $unionType) {
                        if ($type instanceof \ReflectionUnionType) {
                            $type = $unionType;
                        }
                        $types[] = $unionType->getName();
                    }
                }
                if (! $type->allowsNull()) {

                    $type = $type->getName();

                    $newValue = static::makeValueByType($type, $types);
                } else {
                    $newValue = $value;
                }
            } else {
                $newValue = $value;
            }
        } else {
            $newValue = $value;
        }

        if ($isExtended) {
            static::$__parameters[static::class][spl_object_id($this)][$key] = $newValue;
        } else {

            $this->{$key} = $newValue;
        }

        static::fireEvent(['updated', $key], null, $this);

        if ($old != $value) {
            $this->log('set' . ucfirst(Str::camel($key)), ['old' => $old, 'new' => $value]);
        }

        return $this;
    }

    /**
     * Get property value.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        $value = $this->{$key} ?? (static::$__parameters[static::class][spl_object_id($this)][$key] ?? null);
        $mutatorMethodName = 'toArray'.ucfirst(Str::camel($key));
        if (method_exists($this, $mutatorMethodName)) {
            $value = $this->{$mutatorMethodName}($value);
        }
        if (static::isEnumCastable($key)) {
            $value = static::setEnumCastableAttribute($key, $value);
        } else if (static::isClassCastable($key)) {
            $value = static::setClassCastableAttribute($key, $value, $this->vars());
        }

        $this->log('get' . ucfirst(Str::camel($key)), compact('value'));
        return $value;
    }

    /**
     * Map property value.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function map(callable $callback): static
    {
        $params = array_merge(
            $this->getPropertyNames(),
            array_keys(static::$__parameters[static::class][spl_object_id($this)] ?? [])
        );

        foreach ($params as $key) {

            $this->set(
                $key,
                call_user_func(
                    $callback,
                    $this->{$key} ?? static::$__parameters[static::class][spl_object_id($this)][$key],
                    $key
                )
            );
        }

        return $this;
    }

    /**
     * Is property value empty.
     *
     * @param  string  $key
     * @return bool
     */
    public function isEmpty(string $key): bool
    {
        $val = $this->get($key);

        if ($val instanceof Collection) {
            return $val->isEmpty();
        }

        return empty($val);
    }

    /**
     * Is property value not empty.
     *
     * @param  string  $key
     * @return bool
     */
    public function isNotEmpty(string $key): bool
    {
        return !$this->isEmpty($key);
    }

    /**
     * Is property value null.
     *
     * @param  string  $key
     * @return bool
     */
    public function isNull(string $key): bool
    {
        return is_null($this->get($key));
    }

    /**
     * Is property value not null.
     *
     * @param  string  $key
     * @return bool
     */
    public function isNotNull(string $key): bool
    {
        return !$this->isNull($key);
    }

    /**
     * Is property value can null.
     *
     * @param  string  $key
     * @return bool
     */
    public function isCanNull(string $key): bool
    {
        if (isset(static::$extends[$key])) {
            $types = is_array(static::$extends[$key])
                ? static::$extends[$key]
                : explode('|', static::$extends[$key]);

            return in_array('null', $types);
        }

        $parameters = static::getConstructorParameters();

        foreach ($parameters as $parameter) {
            if ($parameter->getName() == $key) {
                return $parameter->allowsNull();
            }
        }
        return false;
    }

    /**
     * Is property value true.
     *
     * @param  string  $key
     * @return bool
     */
    public function isTrue(string $key): bool
    {
        return $this->get($key) === true;
    }

    /**
     * Is property value false.
     *
     * @param  string  $key
     * @return bool
     */
    public function isFalse(string $key): bool
    {
        return $this->get($key) === false;
    }

    /**
     * Is property value bool.
     *
     * @param  string  $key
     * @return bool
     */
    public function isBool(string $key): bool
    {
        return is_bool($this->get($key));
    }

    /**
     * Is property value equals.
     *
     * @param  string  $key
     * @param $value
     * @return bool
     */
    public function isEquals(string $key, $value): bool
    {
        return $this->get($key) === $value;
    }

    /**
     * Is property value not equals.
     *
     * @param  string  $key
     * @param $value
     * @return bool
     */
    public function isNotEquals(string $key, $value): bool
    {
        return $this->get($key) !== $value;
    }

    /**
     * Is property value instance of.
     *
     * @param  string  $key
     * @param  string  $instance
     * @return bool
     */
    public function isInstanceOf(string $key, string $instance): bool
    {
        return $this->get($key) instanceof $instance;
    }

    /**
     * Is property value not instance of.
     *
     * @param  string  $key
     * @param  string  $instance
     * @return bool
     */
    public function isNotInstanceOf(string $key, string $instance): bool
    {
        return !$this->isInstanceOf($key, $instance);
    }

    /**
     * Is property value string.
     *
     * @param  string  $key
     * @return bool
     */
    public function isString(string $key): bool
    {
        return is_string($this->get($key));
    }

    /**
     * Is property value not string.
     *
     * @param  string  $key
     * @return bool
     */
    public function isNotString(string $key): bool
    {
        return !$this->isString($key);
    }

    /**
     * Is property value int.
     *
     * @param  string  $key
     * @return bool
     */
    public function isInt(string $key): bool
    {
        return is_int($this->get($key));
    }

    /**
     * Is property value not int.
     *
     * @param  string  $key
     * @return bool
     */
    public function isNotInt(string $key): bool
    {
        return !$this->isInt($key);
    }

    /**
     * Is property value float.
     *
     * @param  string  $key
     * @return bool
     */
    public function isFloat(string $key): bool
    {
        return is_float($this->get($key));
    }

    /**
     * Is property value not float.
     *
     * @param  string  $key
     * @return bool
     */
    public function isNotFloat(string $key): bool
    {
        return !$this->isFloat($key);
    }

    /**
     * Is property value array.
     *
     * @param  string  $key
     * @return bool
     */
    public function isArray(string $key): bool
    {
        return is_array($this->get($key));
    }

    /**
     * Is property value not array.
     *
     * @param  string  $key
     * @return bool
     */
    public function isNotArray(string $key): bool
    {
        return !$this->isArray($key);
    }

    /**
     * Is property value object.
     *
     * @param  string  $key
     * @return bool
     */
    public function isObject(string $key): bool
    {
        return is_object($this->get($key));
    }

    /**
     * Is property value not object.
     *
     * @param  string  $key
     * @return bool
     */
    public function isNotObject(string $key): bool
    {
        return !$this->isObject($key);
    }

    /**
     * Is property value instance of array.
     *
     * @param  string  $key
     * @param  string  $instance
     * @return bool
     */
    public function isInstanceOfArray(string $key, string $instance): bool
    {
        $value = $this->get($key);

        if ($value && !is_array($value) && !$value instanceof Collection) {
            return false;
        }

        foreach ($value as $item) {
            if (! is_subclass_of($item, $instance)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Is property value not instance of array.
     *
     * @param  string  $key
     * @param  string  $instance
     * @return bool
     */
    public function isNotInstanceOfArray(string $key, string $instance): bool
    {
        return !$this->isInstanceOfArray($key, $instance);
    }
}

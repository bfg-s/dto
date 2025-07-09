<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Default\DiagnoseDto;
use Bfg\Dto\Default\LogsInnerDto;
use Bfg\Dto\Dto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

/**
 * @template TModel of Model|null
 */
trait DtoHelpersTrait
{
    public const GET_LENGTH_SERIALIZE = 1;
    public const GET_LENGTH_JSON = 2;

    /**
     * Make new instance from args
     *
     * @param ...$args
     * @return static
     */
    public static function new(...$args): static
    {
        $parameters = static::getConstructorParameters();
        $model = null;
        if (isset($args['__model'])) {
            if (
                ($argModel = is_string($args['__model']) && class_exists($args['__model'])
                    ? app($args['__model'])
                    : $args['__model']) instanceof Model
            ) {
                $model = $argModel;
                unset($args['__model']);
            }
        }
        $args = collect($args)->mapWithKeys(function ($item, $key) use ($parameters) {
            return [
                is_int($key) ? $parameters[$key]->getName() : $key => $item
            ];
        })->toArray();

        return $args
            ? static::fromArray($args, $model)
            : static::fromEmpty(model: $model);
    }

    /**
     * Get the version of the dto
     *
     * @return string
     */
    public static function version(): string
    {
        return static::$dtoVersion;
    }

    /**
     * @param  array  $through
     * @return mixed
     */
    public function pipeline(array $through): mixed
    {
        return (new Pipeline(app()))
            ->send($this)
            ->through($through)
            ->thenReturn();
    }

    /**
     * @return TModel
     */
    public function model(): Model|null
    {
        return static::$__models[static::class][spl_object_id($this)] ?? null;
    }

    /**
     * Save to current model if exists
     *
     * @return $this
     */
    public function save(): static
    {
        $model = $this->model();

        if ($model) {
            $model->fill($this->toArray());
            $model->save();
        }

        return $this;
    }

    /**
     * Get the property names of the dto
     *
     * @param ...$keys
     * @return array
     */
    public function only(...$keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    /**
     * Set request keys
     *
     * @param  array  $keys
     * @return $this
     */
    public function requestedKeys(array $keys = []): static
    {
        static::$__requestKeys[static::class][spl_object_id($this)] = $keys;

        return $this;
    }

    /**
     * @return $this
     */
    public function camelKeys(): static
    {
        return $this->setSetting('camelKeys', true);
    }

    /**
     * @return $this
     */
    public function snakeKeys(): static
    {
        return $this->setSetting('snakeKeys', true);
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setSetting(string $key, mixed $value): static
    {
        static::$__settings[static::class][spl_object_id($this)][$key] = $value;

        return $this;
    }

    /**
     * @param  string  $key
     * @param  mixed|null  $default
     * @return mixed
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return static::$__settings[static::class][spl_object_id($this)][$key] ?? $default;
    }

    /**
     * @return DiagnoseDto
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public function selfDiagnose(): DiagnoseDto
    {
        $start = static::startTime();

        $logs = $this->logs();
        $explain = $this->explain();
        $computed = $explain->computed;

        $propertiesNewerUsed = [];
        $metaNewerUsed = [];
        $computedNewerUsed = [];
        $totalMs = 0;
        $serializedTimes = $logs->logs->where('message', 'serialized')->count();
        $unserializedTimes = $logs->logs->where('message', 'unserialized')->count();

        foreach (static::getConstructorParameters() as $parameter) {
            $key = 'get' . ucfirst(Str::camel($parameter->getName()));
            if ($logs->logs->where('message', $key)->count() == 0) {
                $propertiesNewerUsed[] = $parameter->getName();
            }
        }

        foreach (static::$extends as $key => $types) {
            $key = 'get' . ucfirst(Str::camel($key));
            if ($logs->logs->where('message', $key)->count() == 0) {
                $propertiesNewerUsed[] = $key;
            }
        }

        foreach (static::$__meta[static::class][spl_object_id($this)] ?? [] as $key => $value) {
            if ($logs->logs->where('message', 'getMeta')->filter(fn (LogsInnerDto $log) => $log->context['key'] === $key)->count() == 0) {
                $metaNewerUsed[] = $key;
            }
        }

        foreach ($computed as $item) {
            $key = "computed" . ucfirst(Str::camel($item));
            if ($logs->logs->where('message', $key)->count() == 0) {
                $computedNewerUsed[] = $item;
            }
        }

        foreach ($logs->logs as $log) {
            $totalMs += $log->ms;
        }

        $result = DiagnoseDto::fromArray([
            'totalMs' => $totalMs,
            'serializedTimes' => $serializedTimes,
            'unserializedTimes' => $unserializedTimes,
            'metaNewerUsed' => $metaNewerUsed,
            'computedNewerUsed' => $computedNewerUsed,
            'propertiesNewerUsed' => $propertiesNewerUsed,
        ]);

        $this->log('selfDiagnose', [], static::endTime($start));

        return $result;
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
     * Get first dto parameter value
     *
     * @return mixed
     */
    public function first(): mixed
    {
        $parameters = static::getConstructorParameters();
        if (count($parameters) > 0) {
            $firstParameter = $parameters[array_key_first($parameters)];
            $name = $firstParameter->getName();
        } elseif (count(static::$extends) > 0) {
            $name = array_key_first(static::$extends);
        }

        return isset($name) ? $this->get($name) : null;
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
                } catch (\Throwable) {
                }
            }
        }

        foreach (static::getConstructorParameters() as $parameter) {

            $this->set(
                ...static::createNameValueFromProperty($parameter, $attributes)
            );

            unset($attributes[$parameter->getName()]);
        }

        foreach (static::$extends as $key => $types) {

            $types = is_array($types) ? $types : explode('|', $types);

            $this->set(
                ...static::createNameValueFromExtendedProperty($key, $types, $attributes)
            );

            unset($attributes[$key]);
        }

        foreach ($attributes as $key => $value) {

            $this->set($key, $value);
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
        } elseif ($type === static::GET_LENGTH_SERIALIZE) {
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
        return md5(static::$dtoVersion . $this->toJson());
    }

    /**
     * Generate hash from instance.
     *
     * @return string
     */
    public function hash(): string
    {
        return md5(spl_object_id($this) . static::$dtoVersion . $this->toJson());
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
        $start = static::startTime();
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
            $value = static::fireEvent(['mutating', $key], $value, static::SET_CURRENT_DATA, $this, $arguments);
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
                $nullable = false;
                if ($type instanceof \ReflectionUnionType) {
                    foreach ($type->getTypes() as $unionType) {
                        if ($type instanceof \ReflectionUnionType) {
                            $type = $unionType;
                        }
                        $types[] = $unionType->getName();
                        if ($unionType->getName() === 'null' || $unionType->allowsNull()) {
                            $nullable = true;
                        }
                    }
                }

                if (! $type->allowsNull() && ! $nullable) {

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
            $this->log('set' . ucfirst(Str::camel($key)), ['old' => $old, 'new' => $value], static::endTime($start));
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
        $start = static::startTime();
        $value = $this->{$key} ?? (static::$__parameters[static::class][spl_object_id($this)][$key] ?? null);
        $mutatorMethodName = 'toArray'.ucfirst(Str::camel($key));
        if (method_exists($this, $mutatorMethodName)) {
            $value = $this->{$mutatorMethodName}($value);
        }
        if (static::isEnumCastable($key)) {
            $value = static::setEnumCastableAttribute($key, $value);
        } elseif (static::isClassCastable($key)) {
            $value = static::setClassCastableAttribute($key, $value, $this->vars());
        }

        $this->log('get' . ucfirst(Str::camel($key)), compact('value'), static::endTime($start));
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

        if ($val instanceof DtoCollection) {
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

        if ($value && !is_array($value) && !$value instanceof DtoCollection) {
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

    /**
     * Check if the given class is a DTO or a DTO collection.
     *
     * @param  mixed  $class
     * @param  bool  $collection
     * @return bool
     */
    public static function isDto(mixed $class, bool $collection = false): bool
    {
        return (($collection && is_subclass_of($class, DtoCollection::class)) || ! $collection)
            || is_subclass_of($class, Dto::class);
    }
}

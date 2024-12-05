<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Dto;
use Bfg\Dto\Exceptions\DtoUndefinedCacheException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

trait DtoConstructorTrait
{
    /**
     * The cache of instances
     *
     * @var array
     */
    private static array $cache = [];

    /**
     * Make instance from cache
     *
     * @param  callable|null  $callback
     * @return static
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedCacheException
     */
    public static function fromCache(callable $callback = null): static
    {
        $serialized = Cache::get(static::class);

        if ($serialized) {
            $instance = unserialize($serialized);
            if ($instance instanceof static) {
                return $instance;
            }
        }

        if ($callback) {

            $result = call_user_func($callback);

            if ($result instanceof static) {

                return $result->cache();
            }
        }

        throw new DtoUndefinedCacheException(static::class);
    }

    /**
     * Make dto instances from collection array
     *
     * @param  array  $items
     * @return DtoCollection
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromCollection(array $items): DtoCollection
    {
        return new DtoCollection(array_filter(array_map(function ($item) {

            if (is_array($item)) {
                return static::fromArray($item);
            } else if (is_string($item)) {
                if (static::isJson($item)) {
                    return static::fromJson($item);
                } else if (static::isSerialize($item)) {
                    return static::fromSerialize($item);
                }
            } else if ($item instanceof Model) {
                return static::fromModel($item);
            } else if ($item instanceof Request) {
                return static::fromRequest($item);
            } else if ($item instanceof Dto) {
                return $item;
            } else if ($item instanceof Collection) {
                return static::fromCollection($item->toArray());
            }
            return null;
        }, $items)));
    }

    /**
     * Get instance from cache
     *
     * @param  string  $key
     * @param  callable  $callback
     * @return static
     */
    public static function fromStaticCache(string $key, callable $callback): static
    {
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = $callback();
        }

        return self::$cache[$key];
    }

    /**
     * Make instance from model
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return static
     */
    public static function fromModel(Model $model = null): static
    {
        $data = static::fireEvent('prepareModel', $model?->toArray() ?: [], static::SET_CURRENT_DATA);
        [$dto, $arguments] = static::makeInstanceFromArray($data);
        static::fireEvent('created', [], $dto, $arguments);
        static::fireEvent('fromModel', [], $dto, $arguments);
        $dto->log('createdFromModel', $arguments);
        return $dto;
    }

    /**
     * Dto constructor from serialize
     *
     * @param  string  $serialize
     * @return static
     */
    public static function fromSerialize(string $serialize = null): static
    {
        $serialize = static::fireEvent('prepareSerialize', $serialize, static::SET_CURRENT_DATA);
        $dto = unserialize($serialize);
        static::fireEvent('fromSerialize', [], $dto);
        if ($dto instanceof Dto) {
            $dto->log('createdFromSerialize');
        }
        return $dto;
    }

    /**
     * Dto constructor from json
     *
     * @param  string  $json
     * @return static
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromJson(string $json = null): static
    {
        $data = $json ? json_decode($json, true) : [];
        $data = static::fireEvent('prepareJson', $data, static::SET_CURRENT_DATA);
        [$dto, $arguments] = static::makeInstanceFromArray($data);
        static::fireEvent('created', [], $dto, $arguments);
        static::fireEvent('fromJson', [], $dto, $arguments);
        $dto->log('createdFromJson', $arguments);
        return $dto;
    }

    /**
     * Dto constructor from request
     *
     * @param  \Illuminate\Foundation\Http\FormRequest|\Illuminate\Http\Request|string  $request
     * @return static
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromRequest(FormRequest|Request|string $request = null): static
    {
        if (is_string($request)) {

            $request = app($request);
        }

        if ($request instanceof FormRequest) {
            $data = static::fireEvent('prepareRequest', $request?->validated() ?: [], static::SET_CURRENT_DATA);
            [$dto, $arguments] = static::makeInstanceFromArray($data);
        } else {
            $data = static::fireEvent('prepareRequest', $request?->all() ?: [], static::SET_CURRENT_DATA);
            [$dto, $arguments] = static::makeInstanceFromArray($data);
        }

        static::fireEvent('created', [], $dto, $arguments);
        static::fireEvent('fromRequest', [], $dto, $arguments);

        $dto->log('createdFromRequest', $arguments);

        return $dto;
    }

    /**
     * Dto constructor from array
     *
     * @param  array  $data
     * @return static
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromArray(array $data = null): static
    {
        $data = static::fireEvent('prepareArray', $data ?: [], static::SET_CURRENT_DATA);

        [$dto, $arguments] = static::makeInstanceFromArray($data);

        static::fireEvent('created', [], $dto, $arguments);
        static::fireEvent('fromArray', [], $dto, $arguments);

        $dto->log('createdFromArray', $arguments);

        return $dto;
    }

    /**
     * Dto constructor from empty
     *
     * @return static
     */
    public static function fromEmpty(): static
    {
        $arguments = static::fireEvent('prepareEmpty', [], static::class);

        foreach (static::getConstructorParameters() as $parameter) {
            $type = $parameter->getType();
            $name = $parameter->getName();
            $value = null;

            if (isset($arguments[$name])) {
                continue;
            }

            if ($type instanceof \ReflectionUnionType) {
                foreach ($type->getTypes() as $unionType) {
                    if ($unionType->getName() === 'array') {
                        $type = $unionType;
                        break;
                    } else if (! $unionType->isBuiltin()) {
                        $class = $unionType->getName();
                        if (is_subclass_of($class, Collection::class) || $class === Collection::class) {
                            $type = $unionType;
                            break;
                        }
                    }
                }
            }

            if ($type instanceof \ReflectionUnionType) {
                foreach ($type->getTypes() as $unionType) {
                    $type = $unionType;
                    break;
                }
            }

            if (! $type->isBuiltin()) {
                $class = $type->getName();
                if (is_subclass_of($class, Dto::class)) {
                    $value = $class::fromEmpty();
                } else {
                    [$name, $value] = static::createNameValueFromProperty($parameter);
                }
            } else {
                if (! $type->allowsNull()) {

                    $type = $type->getName();
                    $value = static::makeValueByType($type);
                }
            }

            $arguments[$name] = $value;
        }

        $arguments = static::fireEvent('creating', $arguments, static::SET_CURRENT_DATA);

        $dto = new static(...$arguments);

        static::fireEvent('created', [], $dto, $arguments);
        static::fireEvent('fromEmpty', [], $dto, $arguments);

        $dto->log('createdFromEmpty', $arguments);

        return $dto;
    }
}

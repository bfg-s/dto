<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Dto;
use Bfg\Dto\Exceptions\DtoUndefinedCacheException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

trait DtoConstructorTrait
{
    /**
     * @param  string  $url
     * @param  array|string|null  $query
     * @param  array  $headers
     * @return \Bfg\Dto\Collections\DtoCollection|static|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromGet(
        string $url,
        array|string|null $query = null,
        array $headers = []
    ): DtoCollection|static|null {

        $response = Http::get($url, $query);

        return static::fromHttp($response, $headers);
    }

    /**
     * @param  string  $url
     * @param  array  $data
     * @param  array  $headers
     * @return \Bfg\Dto\Collections\DtoCollection|static|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromPost(
        string $url,
        array $data = [],
        array $headers = []
    ): DtoCollection|static|null {

        $response = Http::post($url, $data);

        return static::fromHttp($response, $headers);
    }

    /**
     * @param  \Illuminate\Http\Client\Response  $response
     * @param  array  $headers
     * @return \Bfg\Dto\Collections\DtoCollection|Dto|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromHttp(
        Response $response,
        array $headers = []
    ): static|DtoCollection|null {

        $headers = array_merge($headers, static::getHeaders());

        foreach ($headers as $key => $header) {

            $response->withHeader($key, $header);
        }

        $body = $response->body();

        return static::fromAnything($body);
    }

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
     * @param  mixed  ...$other
     * @return DtoCollection
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromCollection(array $items, ...$other): DtoCollection
    {
        return new DtoCollection(array_filter(array_map(function ($item) use ($other) {
            return static::fromAnything($item, ...$other);
        }, $items)));
    }

    /**
     * @param  mixed  $item
     * @param  mixed  ...$other
     * @return Dto|DtoCollection|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromAnything(mixed $item = null, ...$other): static|DtoCollection|null
    {
        if (is_array($item)) {
            if (is_assoc($item)) {
                return static::fromArray($item);
            } else {
                return static::fromCollection($item);
            }
        } else if (is_string($item)) {
            if (filter_var($item, FILTER_VALIDATE_URL) !== false) {
                return static::fromGet($item, ...$other);
            } else if (static::isJson($item)) {
                return static::fromJson($item);
            } else if (static::isSerialize($item)) {
                return static::fromSerialize($item);
            } else if (class_exists($item) && is_subclass_of($item, Request::class)) {
                return static::fromRequest($item);
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
        return static::fromEmpty();
    }

    /**
     * Get instance from cache
     *
     * @param  string  $key
     * @param  callable  $callback
     * @return Dto
     */
    public static function fromStaticCache(string $key, callable $callback): static
    {
        if (!isset(self::$__cache[$key])) {
            self::$__cache[$key] = $callback();
        }

        return self::$__cache[$key];
    }

    /**
     * Make instance from model
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return Dto
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
     * @param  string|null  $serialize
     * @return Dto
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
     * @param  string|null  $json
     * @return \Bfg\Dto\Collections\DtoCollection|Dto
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromJson(string $json = null): DtoCollection|static
    {
        $data = $json ? json_decode($json, true) : [];
        if (! is_assoc($data)) {
            return static::fromCollection($data);
        }
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
     * @param  \Illuminate\Foundation\Http\FormRequest|\Illuminate\Http\Request|string|null  $request
     * @return Dto
     */
    public static function fromRequest(FormRequest|Request|string $request = null): static
    {
        if (is_string($request)) {

            $request = app($request);
        }

        if ($request instanceof FormRequest) {
            $data = static::fireEvent('prepareRequest', $request?->validated() ?: [], static::SET_CURRENT_DATA);
            [$dto, $arguments] = static::makeInstanceFromArray($data);
        } else if ($request instanceof Request) {
            $data = static::fireEvent('prepareRequest', $request?->all() ?: [], static::SET_CURRENT_DATA);
            [$dto, $arguments] = static::makeInstanceFromArray($data);
        } else {
            return static::fromEmpty();
        }

        static::fireEvent('created', [], $dto, $arguments);
        static::fireEvent('fromRequest', [], $dto, $arguments);

        $dto->log('createdFromRequest', $arguments);

        return $dto;
    }

    /**
     * Dto constructor from array
     *
     * @param  array|null  $data
     * @return \Bfg\Dto\Collections\DtoCollection|\Bfg\Dto\Dto
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromArray(array $data = null): DtoCollection|static
    {
        if (! is_assoc($data)) {

            return static::fromCollection($data);
        }

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
     * @return Dto
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

            $types = [];
            if ($type instanceof \ReflectionUnionType) {
                foreach ($type->getTypes() as $unionType) {
                    if ($type instanceof \ReflectionUnionType) {
                        $type = $unionType;
                    }
                    $types[] = $type->getName();
                }
            }

            $data = [
                $name => $type->allowsNull() ? null : static::makeValueByType($type->getName(), $types)
            ];

            [$name, $value] = static::createNameValueFromProperty($parameter, $data);

            if (! $value && ! $type->isBuiltin()) {
                $class = $type->getName();
                if (is_subclass_of($class, Dto::class)) {
                    $value = $class::fromEmpty();
                } else if (is_subclass_of($class, Collection::class) || $class === Collection::class) {
                    $value = new DtoCollection();
                }
            }

            $arguments[$name] = $value;
        }

        $arguments = static::fireEvent('creating', $arguments, static::SET_CURRENT_DATA);

        $dto = new static(...$arguments);

        foreach (static::$extends as $key => $types) {
            $types = is_array($types) ? $types : explode('|', $types);
            $type = $types[0];
            [$key, $value] = static::createNameValueFromExtendedProperty($key, $types, [
                $key => in_array('null', $types) ? null : static::makeValueByType($type, $types)
            ]);

            static::$__parameters[static::class][spl_object_id($dto)][$key] = $value;
        }

        static::fireEvent('created', [], $dto, $arguments);
        static::fireEvent('fromEmpty', [], $dto, $arguments);

        $dto->log('createdFromEmpty', $arguments);

        return $dto;
    }
}

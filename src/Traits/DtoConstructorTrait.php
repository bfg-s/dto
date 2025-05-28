<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Collections\LazyDtoCollection;
use Bfg\Dto\Dto;
use Bfg\Dto\Exceptions\DtoHttpRequestException;
use Bfg\Dto\Exceptions\DtoSourceNotFoundException;
use Bfg\Dto\Exceptions\DtoUndefinedCacheException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait DtoConstructorTrait
{
    /**
     * @param  string  $sourceName
     * @param ...$arguments
     * @return Collection|\Bfg\Dto\Dto|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromSource(string $sourceName, ...$arguments): static|Collection|null
    {
        $method = 'source' . ucfirst(Str::camel($sourceName));

        if (method_exists(static::class, $method)) {
            return static::fromAnything(static::$method(...$arguments));
        }

        throw new DtoSourceNotFoundException($sourceName);
    }

    /**
     * @param  string  $filePath
     * @return \Bfg\Dto\Collections\LazyDtoCollection
     */
    public static function fromFile(string $filePath): LazyDtoCollection
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $format = pathinfo($filePath, PATHINFO_EXTENSION);

        if (! in_array($format, ['json', 'csv'])) {
            throw new \InvalidArgumentException("Unsupported format: {$format}");
        }

        return LazyDtoCollection::make(function () use ($filePath, $format) {
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                throw new \RuntimeException("Unable to open file: {$filePath}");
            }

            try {
                while (($line = fgets($handle)) !== false) {
                    $data = match ($format) {
                        'json' => json_decode($line, true),
                        'csv' => str_getcsv($line),
                        default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
                    };

                    if ($data) {
                        yield static::fromArray($data);
                    }
                }
            } finally {
                fclose($handle);
            }
        });
    }

    /**
     * Make instance from URL
     *
     * @param  string  $url
     * @param ...$other
     * @return \Bfg\Dto\Dto|\Illuminate\Support\Collection|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromUrl(string $url, ...$other): Dto|Collection|null
    {
        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            if (static::$postDefault) {
                return static::fromPost($url, ...$other);
            }
            return static::fromGet($url, ...$other);
        }
        return static::fromEmpty();
    }

    /**
     * @param  string  $url
     * @param  array|string|null  $query
     * @param  array  $headers
     * @return Collection|static|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromGet(
        string $url,
        array|string|null $query = null,
        array $headers = []
    ): Collection|static|null {

        return static::fromHttp('get', $url, $query, $headers);
    }

    /**
     * @param  string  $url
     * @param  array  $data
     * @param  array  $headers
     * @return Collection|static|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromPost(
        string $url,
        array $data = [],
        array $headers = []
    ): Collection|static|null {

        return static::fromHttp('post', $url, $data, $headers);
    }

    /**
     * @param  string  $method
     * @param  string  $url
     * @param  array|string|null  $data
     * @param  array  $headers
     * @return Collection|Dto|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromHttp(string $method, string $url, array|string|null $data = [], array $headers = []): Collection|static|null
    {
        $method = strtolower($method);
        if (! in_array($method, ['get', 'head', 'post', 'put', 'patch', 'delete'])) {
            $method = 'get';
        }
        $response = static::httpClient()
            ->withHeaders(array_merge($headers, static::httpHeaders()))
            ->{$method}($url, static::httpData($data));

        if ($response->status() >= 400) {
            throw new DtoHttpRequestException($response->body());
        }

        return static::fromAnything(
            $response->body()
        );
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
     * @param  \Illuminate\Support\Collection|array  $items
     * @param  mixed  ...$other
     * @return Collection
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromCollection(Collection|array $items, ...$other): Collection
    {
        $collectionClass = is_array($items) ? DtoCollection::class : $items::class;

        if ($items instanceof Collection) {
            $items = $items->toArray();
        }

        return new ($collectionClass)(array_filter(array_map(function ($item) use ($other) {
            return static::fromAnything($item, ...$other);
        }, $items)));
    }

    /**
     * @param  mixed  $item
     * @param  mixed  ...$other
     * @return Dto|Collection|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromAnything(mixed $item = null, ...$other): static|Collection|null
    {
        if (is_array($item)) {
            return static::fromArray($item);
        } elseif (is_string($item)) {
            if (static::isJson($item)) {
                return static::fromJson($item);
            } elseif (static::isSerialize($item)) {
                return static::fromSerialize($item);
            } elseif (class_exists($item)) {
                if (is_subclass_of($item, Request::class)) {
                    return static::fromRequest($item);
                } elseif (is_subclass_of($item, Dto::class)) {
                    return static::fromDto($item);
                }
            } else {
                return static::fromUrl($item, ...$other);
            }
        } elseif ($item instanceof Model) {
            return static::fromModel($item);
        } elseif ($item instanceof Request) {
            return static::fromRequest($item);
        } elseif ($item instanceof Dto) {
            return static::fromDto($item);
        } elseif ($item instanceof Collection) {
            return static::fromCollection($item);
        }
        return static::fromEmpty();
    }

    /**
     * Make instance from DTO
     *
     * @param  \Bfg\Dto\Dto|class-string<Dto>  $dto
     * @return \Bfg\Dto\Dto|\Illuminate\Support\Collection
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromDto(Dto|string $dto): Dto|Collection
    {
        if (is_string($dto)) {
            $dto = app($dto);
        }
        return static::fromArray($dto->toArray());
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
        $start = static::startTime();
        $model = static::configureModel($model);
        $data = static::fireEvent('prepareModel', [], static::SET_CURRENT_DATA);
        [$dto, $arguments] = static::makeInstanceFromArray($data, $model);
        static::$__models[static::class][spl_object_id($dto)] = $model;
        static::fireEvent('created', [], $dto, $arguments);
        static::fireEvent('fromModel', [], $dto, $arguments);
        $dto->log('createdFromModel', [], ms: static::endTime($start));
        return $dto;
    }

    /**
     * Dto constructor from serialize
     *
     * @param  string|null  $serialize
     * @return static|\Illuminate\Support\Collection|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromSerialize(string $serialize = null): static|Collection|null
    {
        $start = static::startTime();
        $serialize = static::fireEvent('prepareSerialize', $serialize, static::SET_CURRENT_DATA);
        $dto = unserialize($serialize);
        static::fireEvent('fromSerialize', [], $dto);
        if ($dto instanceof Dto) {
            $dto->log('createdFromSerialize', ms: static::endTime($start));
            return $dto;
        }
        return static::fromAnything($dto);
    }

    /**
     * Dto constructor from json
     *
     * @param  string|null  $json
     * @return Collection|Dto
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromJson(string $json = null): Collection|static
    {
        $start = static::startTime();
        $data = $json ? json_decode($json, true) : [];
        if (! is_assoc($data)) {
            return static::fromCollection($data);
        }
        $data = static::fireEvent('prepareJson', $data, static::SET_CURRENT_DATA);
        [$dto, $arguments] = static::makeInstanceFromArray($data);
        static::fireEvent('created', [], $dto, $arguments);
        static::fireEvent('fromJson', [], $dto, $arguments);
        $dto->log('createdFromJson', [], ms: static::endTime($start));
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
        $start = static::startTime();

        if (is_string($request)) {

            $request = app($request);
        }

        if ($request instanceof FormRequest) {
            $data = static::fireEvent('prepareRequest', $request?->validated() ?: [], static::SET_CURRENT_DATA);
            [$dto, $arguments] = static::makeInstanceFromArray($data);
        } elseif ($request instanceof Request) {
            $data = static::fireEvent('prepareRequest', $request?->all() ?: [], static::SET_CURRENT_DATA);
            [$dto, $arguments] = static::makeInstanceFromArray($data);
        } else {
            return static::fromEmpty();
        }

        static::fireEvent('created', [], $dto, $arguments);
        static::fireEvent('fromRequest', [], $dto, $arguments);

        $dto->requestedKeys(array_keys($data ?: []));
        $dto->log('createdFromRequest', [], ms: static::endTime($start));

        return $dto;
    }

    /**
     * Dto constructor from array
     *
     * @param  array|null  $data
     * @return Collection|\Bfg\Dto\Dto
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromArray(array $data = null): Collection|static
    {
        $start = static::startTime();

        if (! $data) {

            return static::fromEmpty();
        }

        if (! is_assoc($data)) {

            return static::fromCollection($data);
        }

        $data = static::fireEvent('prepareArray', $data ?: [], static::SET_CURRENT_DATA);

        [$dto, $arguments] = static::makeInstanceFromArray($data);

        static::fireEvent('created', [], $dto, $arguments);
        static::fireEvent('fromArray', [], $dto, $arguments);

        $dto->log('createdFromArray', [], ms: static::endTime($start));

        return $dto;
    }

    /**
     * Dto constructor from empty
     *
     * @return Dto
     */
    public static function fromEmpty(): static
    {
        $start = static::startTime();

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
                    } elseif (! $unionType->isBuiltin()) {
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
                $name => $parameter->isDefaultValueAvailable()
                    ? $parameter->getDefaultValue()
                    : (! $type->allowsNull()
                        ? static::makeValueByType($type->getName(), $types)
                        : null)
            ];

            [$name, $value] = static::createNameValueFromProperty($parameter, $data);

            if (! $value && ! $type->isBuiltin()) {
                $class = $type->getName();
                if (is_subclass_of($class, Dto::class)) {
                    $value = $class::fromEmpty();
                } elseif (is_subclass_of($class, Collection::class) || $class === Collection::class) {
                    $value = new $class();
                }
            }

            if ($value === null) {
                $methodByDefault = 'default' . ucfirst(Str::camel($name));
                if (method_exists(static::class, $methodByDefault)) {
                    $value = static::$methodByDefault();
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

            if ($value === null) {
                $methodByDefault = 'default' . ucfirst(Str::camel($key));
                if (method_exists(static::class, $methodByDefault)) {
                    $value = static::$methodByDefault();
                }
            }

            static::$__parameters[static::class][spl_object_id($dto)][$key] = $value;
        }

        static::fireEvent('created', [], $dto, $arguments);
        static::fireEvent('fromEmpty', [], $dto, $arguments);

        $dto->log('createdFromEmpty', [], ms: static::endTime($start));

        return $dto;
    }
}

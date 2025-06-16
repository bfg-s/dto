<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Collections\LazyDtoCollection;
use Bfg\Dto\Dto;
use Bfg\Dto\Exceptions\DtoHttpRequestException;
use Bfg\Dto\Exceptions\DtoSourceNotFoundException;
use Bfg\Dto\Exceptions\DtoUndefinedCacheException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

trait DtoConstructorTrait
{
    /**
     * Make instance from source
     *
     * @param  string  $sourceName
     * @param ...$arguments
     * @return DtoCollection<static>|static|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromSource(string $sourceName, ...$arguments): DtoCollection|static|null
    {
        $method = 'source'.ucfirst(Str::camel($sourceName));

        if (method_exists(static::class, $method)) {
            return static::from(static::$method(...$arguments));
        }

        throw new DtoSourceNotFoundException($sourceName);
    }

    /**
     * Make an instance from a file
     *
     * @param  string  $filePath
     * @return LazyDtoCollection<static>
     */
    public static function fromFile(string $filePath): LazyDtoCollection
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $format = pathinfo($filePath, PATHINFO_EXTENSION);

        if (!in_array($format, ['json', 'csv'])) {
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
     * Make an instance from URL
     *
     * @param  string  $url
     * @param ...$other
     * @return DtoCollection<static>|static|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromUrl(string $url, ...$other): DtoCollection|static
    {
        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            if (static::$postDefault) {
                $return = static::fromPost($url, ...$other);
            } else {
                $return = static::fromGet($url, ...$other);
            }
        } else {
            $return = static::fromEmpty();
        }
        call_user_func([$return, 'setImportType'], 'url', $url);
        return $return;
    }

    /**
     * Make an instance from a GET request
     *
     * @param  string  $url
     * @param  array|string|null  $query
     * @param  array  $headers
     * @return DtoCollection<static>|static|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromGet(
        string $url,
        array|string|null $query = null,
        array $headers = []
    ): DtoCollection|static {
        return static::fromHttp('get', $url, $query, $headers);
    }

    /**
     * Make an instance from a POST request
     *
     * @param  string  $url
     * @param  array  $data
     * @param  array  $headers
     * @return DtoCollection<static>|static|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromPost(
        string $url,
        array $data = [],
        array $headers = []
    ): DtoCollection|static {
        return static::fromHttp('post', $url, $data, $headers);
    }

    /**
     * Make an instance from an HTTP request
     *
     * @param  string  $method
     * @param  string  $url
     * @param  array|string|null  $data
     * @param  array  $headers
     * @return DtoCollection<static>|static|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromHttp(
        string $method,
        string $url,
        array|string|null $data = [],
        array $headers = []
    ): DtoCollection|static {
        static::$source = $url;
        $method = strtolower($method);
        if (!in_array($method, ['get', 'head', 'post', 'put', 'patch', 'delete'])) {
            $method = 'get';
        }
        $response = static::httpClient()
            ->withHeaders(array_merge($headers, static::httpHeaders()))
            ->{$method}($url, static::httpData($data));

        if ($response->status() >= 400) {
            throw new DtoHttpRequestException($response->body());
        }

        return static::from(
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
    public static function fromCache(callable|null $callback = null): static
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
     * Make dto instances from a collection array
     *
     * @param  \Illuminate\Support\Collection|array  $items
     * @param  mixed  ...$other
     * @return DtoCollection<static>
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromCollection(mixed $items, ...$other): DtoCollection
    {
        if ($items instanceof Arrayable) {
            $items = $items->toArray();
        }

        if (empty($items)) {
            return new DtoCollection();
        }

        if (
            (is_array($items) && is_assoc($items))
            || ! is_array($items)
        ) {
            $items = [$items];
        }

        return (new DtoCollection(array_filter(array_map(function ($item) use ($other) {
            return static::from($item, ...$other);
        }, $items))))->setRoot(static::class);
    }

    /**
     * Make an instance from anything
     *
     * @param  mixed  $item
     * @param  mixed  ...$other
     * @return DtoCollection<static>|static
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     * @deprecated Use `from` method instead.
     */
    public static function fromAnything(mixed $item = null, ...$other): DtoCollection|static
    {
        return static::from($item, ...$other);
    }

    /**
     * Make an instance from any type
     *
     * @param  mixed|null  $item
     * @param ...$other
     * @return \Bfg\Dto\Collections\DtoCollection<static>|static
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function from(mixed $item = null, ...$other): DtoCollection|static
    {
        if (is_null($item)) {
            return static::fromEmpty();
        } elseif (is_countable($item)) {
            return static::fromArray($item);
        } elseif (is_string($item)) {
            return static::fromString($item, null, ...$other);
        } elseif (is_object($item)) {
            return static::fromObject($item);
        } elseif (is_numeric($item) || is_bool($item)) {
            return static::new($item);
        } elseif (is_callable($item)) {
            return static::from(call_user_func_array($item, $other), ...$other);
        } elseif (is_resource($item)) {
            $data = stream_get_contents($item);
            fclose($item);
            if ($data === false) {
                return static::fromEmpty();
            }
            return static::from($data, ...$other);
        }

        return static::fromEmpty();
    }

    /**
     * Make an instance from an any object
     *
     * @param  object|null  $object
     * @return \Bfg\Dto\Collections\DtoCollection|static
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromObject(object|null $object = null): DtoCollection|static
    {
        if ($object instanceof Model) {
            return static::fromModel($object);
        } elseif ($object instanceof Request) {
            return static::fromRequest($object);
        } elseif ($object instanceof Dto) {
            return static::fromDto($object);
        } elseif ($object instanceof Collection) {
            return static::fromCollection($object);
        } elseif ($object instanceof Fluent) {
            return static::fromFluent($object);
        } else if ($object instanceof Arrayable) {
            return static::fromArray($object);
        }
        return static::fromArray(get_object_vars($object));
    }

    /**
     * Make an instance from DTO
     *
     * @param  \Bfg\Dto\Dto|class-string<Dto>  $dto
     * @return static
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromDto(Dto|string $dto): static
    {
        if (is_string($dto)) {
            $dto = app($dto);
        }
        return static::fromArray($dto->toArray());
    }

    /**
     * Make an instance from Fluent
     *
     * @param  \Illuminate\Support\Fluent  $fluent
     * @return DtoCollection<static>|static
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromFluent(Fluent $fluent): DtoCollection|static
    {
        return static::fromArray($fluent->toArray());
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
        if (!isset(self::$__cache[$key])) {
            self::$__cache[$key] = $callback();
        }

        return self::$__cache[$key];
    }

    /**
     * Make an instance from a model
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return static
     */
    public static function fromModel(Model|null $model = null): static
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
     * @return DtoCollection<static>|static|null
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromSerialize(string|null $serialize = null): DtoCollection|static
    {
        $start = static::startTime();
        $serialize = static::fireEvent('prepareSerialize', $serialize, static::SET_CURRENT_DATA);
        $dto = unserialize($serialize);
        static::fireEvent('fromSerialize', [], $dto);
        if ($dto instanceof Dto || $dto instanceof DtoCollection) {
            $dto->log('createdFromSerialize', ms: static::endTime($start));
            $return = $dto;
            call_user_func([$return, 'setImportType'], 'serializeDto', $serialize);
        } else {
            $return = static::from($dto);
            call_user_func([$return, 'setImportType'], 'serializeAny', $serialize);
        }
        return $return;
    }

    /**
     * Dto constructor from JSON
     *
     * @param  string|null  $json
     * @return DtoCollection<static>|static
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromJson(string|null $json = null): DtoCollection|static
    {
        $start = static::startTime();
        $data = $json ? json_decode($json, true) : [];
        if (!is_assoc($data)) {
            $return = static::fromCollection($data);
        } else {
            $data = static::fireEvent('prepareJson', $data, static::SET_CURRENT_DATA);
            [$dto, $arguments] = static::makeInstanceFromArray($data);
            static::fireEvent('created', [], $dto, $arguments);
            static::fireEvent('fromJson', [], $dto, $arguments);
            $dto->log('createdFromJson', [], ms: static::endTime($start));
            $return = $dto;
        }
        call_user_func([$return, 'setImportType'], 'json', $json);
        return $return;
    }

    /**
     * Dto constructor from request
     *
     * @param  FormRequest|Request|class-string<FormRequest|Request>|null  $request
     * @return static
     */
    public static function fromRequest(FormRequest|Request|string|null $request = null): static
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
     * Dto constructor from an array
     *
     * @param  mixed  $data
     * @return DtoCollection<static>|static
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromArray(mixed $data = null): DtoCollection|static
    {
        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } elseif (is_iterable($data)) {
            $data = is_array($data) ? $data : iterator_to_array($data);
        } else {
            $data = [];
        }

        $start = static::startTime();

        if (!$data) {
            return static::fromEmpty();
        }

        if (!is_assoc($data)) {
            return static::fromCollection($data);
        }

        $data = static::fireEvent('prepareArray', $data, static::SET_CURRENT_DATA);

        [$dto, $arguments] = static::makeInstanceFromArray($data);

        static::fireEvent('created', [], $dto, $arguments);
        static::fireEvent('fromArray', [], $dto, $arguments);

        $dto->log('createdFromArray', [], ms: static::endTime($start));

        return $dto;
    }

    /**
     * Dto constructor from empty
     *
     * @param  array<string, mixed>  $data
     * @return static
     */
    public static function fromEmpty(array $data = []): static
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
                    } elseif (!$unionType->isBuiltin()) {
                        $class = $unionType->getName();
                        if (is_subclass_of($class, DtoCollection::class) || $class === DtoCollection::class) {
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

            $allData = [
                $name => $parameter->isDefaultValueAvailable()
                    ? $parameter->getDefaultValue()
                    : (!$type->allowsNull()
                        ? static::makeValueByType($type->getName(), $types)
                        : null),
                ...$data
            ];

            [$name, $value] = static::createNameValueFromProperty($parameter, $allData);

            if (!$value && !$type->isBuiltin()) {
                $class = $type->getName();
                if (is_subclass_of($class, Dto::class)) {
                    $value = $class::fromEmpty();
                } elseif (is_subclass_of($class, DtoCollection::class) || $class === DtoCollection::class) {
                    $value = new $class();
                }
            }

            if ($value === null) {
                $methodByDefault = 'default'.ucfirst(Str::camel($name));
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
                $methodByDefault = 'default'.ucfirst(Str::camel($key));
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

    /**
     * Create a new instance from a string
     *
     * @param  string  $string
     * @param  string|null  $separator
     * @param  mixed  ...$other
     * @return DtoCollection|static
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromString(string $string, string|null $separator = null, ...$other): DtoCollection|static
    {
        if ($separator !== null) {
            $data = explode($separator, $string);
            return static::new(...$data);
        }

        if (static::isJson($string)) {
            return static::fromJson($string);
        } elseif (static::isSerialize($string)) {
            return static::fromSerialize($string);
        } elseif (class_exists($string)) {
            return static::fromClassString($string);
        } elseif (filter_var($string, FILTER_VALIDATE_URL) !== false) {
            return static::fromUrl($string, ...$other);
        }

        return static::new($string);
    }

    /**
     * Make an instance from a class string
     *
     * @param  string|null  $class
     * @return \Bfg\Dto\Collections\DtoCollection|static
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromClassString(string|null $class): DtoCollection|static
    {
        if (is_subclass_of($class, Request::class)) {
            return static::fromRequest($class);
        } elseif (is_subclass_of($class, Dto::class)) {
            return static::fromDto($class);
        }

        return static::fromContainer($class);
    }

    /**
     * Make an instance from a container abstract
     *
     * @param  string  $abstract
     * @return \Bfg\Dto\Collections\DtoCollection|static
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public static function fromContainer(string $abstract): DtoCollection|static
    {
        return static::fromObject(app($abstract));
    }
}

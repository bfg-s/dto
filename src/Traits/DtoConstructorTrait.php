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

/**
 * @template TModel of Model|null
 */
trait DtoConstructorTrait
{
    /**
     * Make an instance from any type
     *
     * @param  mixed|null  $item
     * @param  TModel|Model|null  $model
     * @return \Bfg\Dto\Collections\DtoCollection<int, static<TModel>>|static<TModel>
     */
    public static function from(mixed $item = null, Model|null $model = null): DtoCollection|static
    {
        if (is_null($item)) {
            return static::fromEmpty(model: $model);
        } elseif (is_iterable($item)) {
            return static::fromArray($item, $model);
        } elseif (is_string($item)) {
            return static::fromString($item, null, $model);
        } elseif (is_object($item)) {
            return static::fromObject($item, $model);
        } elseif (is_callable($item)) {
            return static::fromCallable($item, $model);
        } elseif (is_resource($item)) {
            return static::fromResource($item, $model);
        } elseif (is_numeric($item)) {
            return static::fromNumeric($item, $model);
        }

        return static::new($item, __model: $model);
    }

    /**
     * Make an instance from a callable
     *
     * @param  callable  $cb
     * @param  TModel|Model|null  $model
     * @return \Bfg\Dto\Collections\DtoCollection<int, static<TModel>>|static<TModel>
     */
    public static function fromCallable(callable $cb, Model|null $model = null): DtoCollection|static
    {
        return static::from(call_user_func($cb, static::class, $model), $model);
    }

    /**
     * Make an instance from a resource
     *
     * @param  mixed  $resource
     * @param  TModel|Model|null  $model
     * @return \Bfg\Dto\Collections\DtoCollection<int, static<TModel>>|static<TModel>
     */
    public static function fromResource(mixed $resource, Model|null $model = null): DtoCollection|static
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException('Expected a resource, got: ' . gettype($resource));
        }
        $data = stream_get_contents($resource);
        fclose($resource);
        if ($data === false) {
            return static::fromEmpty(model: $model);
        }
        return static::from($data, $model);
    }

    /**
     * Make instance from source
     *
     * @param  string  $sourceName
     * @param ...$arguments
     * @return DtoCollection<int, static<TModel>>|static<TModel>
     */
    public static function fromSource(string $sourceName, ...$arguments): DtoCollection|static
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
     * @param  TModel|Model|null  $model
     * @return LazyDtoCollection<int, static<TModel>>
     */
    public static function fromFile(string $filePath, Model|null $model = null): LazyDtoCollection
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $format = pathinfo($filePath, PATHINFO_EXTENSION);

        if (!in_array($format, ['json', 'csv'])) {
            throw new \InvalidArgumentException("Unsupported format: {$format}");
        }

        if ($model) {
            LazyDtoCollection::setModelFor(static::class, $model);
        }

        return LazyDtoCollection::make(function () use ($filePath, $format, $model) {
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
                        yield static::fromArray($data, $model);
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
     * @param  TModel|Model|null  $model
     * @return \Bfg\Dto\Collections\DtoCollection<int, static<TModel>>|static<TModel>
     */
    public static function fromUrl(string $url, Model|null $model = null): DtoCollection|static
    {
        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            $return = static::fromHttp(static::$defaultHttpMethod, $url, $model);
        } else {
            $return = static::fromEmpty(model: $model);
        }
        $return::setImportType('url', $url, instance: $return);
        return $return;
    }

    /**
     * Make an instance from a GET request
     *
     * @param  string  $url
     * @param  TModel|Model|null  $model
     * @return \Bfg\Dto\Collections\DtoCollection<int, static<TModel>>|static<TModel>
     */
    public static function fromGet(string $url, Model|null $model = null): DtoCollection|static
    {
        return static::fromHttp('get', $url, $model);
    }

    /**
     * Make an instance from a POST request
     *
     * @param  string  $url
     * @param  TModel|Model|null  $model
     * @return \Bfg\Dto\Collections\DtoCollection<int, static<TModel>>|static<TModel>
     */
    public static function fromPost(string $url, Model|null $model = null): DtoCollection|static
    {
        return static::fromHttp('post', $url, $model);
    }

    /**
     * Make an instance from an HTTP request
     *
     * @param  string  $method
     * @param  string  $url
     * @param  TModel|Model|null  $model
     * @return \Bfg\Dto\Collections\DtoCollection<int, static<TModel>>|static<TModel>
     */
    public static function fromHttp(string $method, string $url, Model|null $model = null): DtoCollection|static
    {
        static::$__source = $url;
        $method = strtolower($method);
        if (!in_array($method, ['get', 'head', 'post', 'put', 'patch', 'delete'])) {
            $method = 'get';
        }
        $response = static::httpClient()
            ->withHeaders(static::httpHeaders())
            ->{$method}($url, static::httpData());

        if ($response->status() >= 400) {
            throw new DtoHttpRequestException($response->body());
        }

        return static::from($response->body(), $model);
    }

    /**
     * Make instance from cache
     *
     * @param  callable|null  $callback
     * @param  TModel|Model|null  $model
     * @return static<TModel>
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedCacheException
     */
    public static function fromCache(callable|null $callback = null, Model|null $model = null): static
    {
        $serialized = Cache::get(static::class);

        if ($serialized) {
            $instance = unserialize($serialized);
            if ($instance instanceof static) {
                $instance->setModel($model);
                return $instance;
            }
        }

        if ($callback) {
            $result = call_user_func($callback);

            if ($result instanceof static) {
                $result->setModel($model);
                return $result->cache();
            }
        }

        throw new DtoUndefinedCacheException(static::class);
    }

    /**
     * Make dto instances from a collection array
     *
     * @param  \Illuminate\Support\Collection|array  $items
     * @param  TModel|Model|null  $model
     * @return DtoCollection<int, static<TModel>>
     */
    public static function fromCollection(mixed $items = [], Model|null $model = null): DtoCollection
    {
        if ($items instanceof Arrayable) {
            $items = $items->toArray();
        }

        if ($model) {
            DtoCollection::setModelFor(static::class, $model);
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

        return (new DtoCollection(array_filter(array_map(function ($item) use ($model) {
            return static::from($item, $model);
        }, $items))))->setRoot(static::class);
    }

    /**
     * Make an instance from anything
     *
     * @param  mixed  $item
     * @param  TModel|Model|null  $model
     * @return DtoCollection<int, static<TModel>>|static<TModel>
     * @deprecated Use `from` method instead.
     */
    public static function fromAnything(mixed $item = null, Model|null $model = null): DtoCollection|static
    {
        return static::from($item, $model);
    }

    /**
     * Make an instance from an any object
     *
     * @param  object|null  $object
     * @param  TModel|Model|null  $model
     * @return \Bfg\Dto\Collections\DtoCollection<int, static<TModel>>|static<TModel>
     */
    public static function fromObject(object|null $object = null, Model|null $model = null): DtoCollection|static
    {
        if ($object instanceof Model) {
            return static::fromModel($object);
        } elseif ($object instanceof Request) {
            return static::fromRequest($object, $model);
        } elseif ($object instanceof Dto) {
            return static::fromDto($object, $model);
        } elseif ($object instanceof Collection) {
            return static::fromCollection($object, $model);
        } elseif ($object instanceof Fluent) {
            return static::fromFluent($object, $model);
        } else if ($object instanceof Arrayable) {
            return static::fromArray($object, $model);
        }
        return static::fromArray(get_object_vars($object), $model);
    }

    /**
     * Make an instance from DTO
     *
     * @param  \Bfg\Dto\Dto|class-string<Dto>  $dto
     * @param  TModel|Model|null  $model
     * @return static<TModel>
     */
    public static function fromDto(Dto|string $dto, Model|null $model = null): static
    {
        if (is_string($dto)) {
            $abstract = $dto;
            $dto = app($abstract);
            if (! ($dto instanceof Dto)) {
                throw new \InvalidArgumentException("Unsupported dto format: {$abstract}");
            }
        }
        return static::fromArray($dto->toArray(), $model);
    }

    /**
     * Make an instance from Fluent
     *
     * @param  \Illuminate\Support\Fluent  $fluent
     * @param  TModel|Model|null  $model
     * @return DtoCollection<int, static<TModel>>|static<TModel>
     */
    public static function fromFluent(Fluent $fluent, Model|null $model = null): DtoCollection|static
    {
        return static::fromArray($fluent->toArray(), $model);
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
     * @param  TModel|Model|null  $model
     * @return static<TModel>
     */
    public static function fromModel(Model|null $model = null): static
    {
        $start = static::startTime();
        $model = static::configureModel($model);
        $data = static::fireEvent('prepareModel', [], static::SET_CURRENT_DATA);
        [$dto, $arguments] = static::makeInstanceFromArray($data, $model);
        static::fireEvent('created', [], $dto, $arguments);
        static::fireEvent('fromModel', [], $dto, $arguments);
        $dto->log('createdFromModel', [], ms: static::endTime($start));
        return $dto;
    }

    /**
     * Dto constructor from serialize
     *
     * @param  string|null  $serialize
     * @param  TModel|Model|null  $model
     * @return \Bfg\Dto\Collections\DtoCollection<int, static<TModel>>|static<TModel>
     */
    public static function fromSerialize(string|null $serialize = null, Model|null $model = null): DtoCollection|static
    {
        $start = static::startTime();
        $serialize = static::fireEvent('prepareSerialize', $serialize, static::SET_CURRENT_DATA);
        $dto = unserialize($serialize);
        static::fireEvent('fromSerialize', [], $dto);
        if ($dto instanceof Dto || $dto instanceof DtoCollection) {
            $dto->setModel($model);
            $dto->log('createdFromSerialize', ms: static::endTime($start));
            $dto::setImportType('serialize', $serialize, instance: $dto);
            $return = $dto;
        } else {
            $return = static::from($dto, $model);
            $return::setImportType('serializeAny', $serialize, instance: $return);
        }
        return $return;
    }

    /**
     * Dto constructor from JSON
     *
     * @param  string|null  $json
     * @param  TModel|Model|null  $model
     * @return DtoCollection<int, static<TModel>>|static<TModel>
     */
    public static function fromJson(string|null $json = null, Model|null $model = null): DtoCollection|static
    {
        $start = static::startTime();
        $data = $json ? json_decode($json, true) : [];
        if (!is_assoc($data)) {
            $return = static::fromCollection($data, $model);
        } else {
            $data = static::fireEvent('prepareJson', $data, static::SET_CURRENT_DATA);
            [$dto, $arguments] = static::makeInstanceFromArray($data, $model);
            static::fireEvent('created', [], $dto, $arguments);
            static::fireEvent('fromJson', [], $dto, $arguments);
            $dto->log('createdFromJson', [], ms: static::endTime($start));
            $return = $dto;
        }
        $return::setImportType('json', $json, instance: $return);
        return $return;
    }

    /**
     * Dto constructor from request
     *
     * @param  FormRequest|Request|class-string<FormRequest|Request>|null  $request
     * @param  TModel|Model|null  $model
     * @return static<TModel>
     */
    public static function fromRequest(FormRequest|Request|string|null $request = null, Model|null $model = null): static
    {
        $start = static::startTime();

        if (is_string($request)) {
            $request = app($request);
        }

        if ($request instanceof FormRequest) {
            $data = static::fireEvent('prepareRequest', $request?->validated() ?: [], static::SET_CURRENT_DATA);
            [$dto, $arguments] = static::makeInstanceFromArray($data, $model);
        } elseif ($request instanceof Request) {
            $data = static::fireEvent('prepareRequest', $request?->all() ?: [], static::SET_CURRENT_DATA);
            [$dto, $arguments] = static::makeInstanceFromArray($data, $model);
        } else {
            return static::fromEmpty(model: $model);
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
     * @param  TModel|Model|null  $model
     * @return DtoCollection<int, static<TModel>>|static<TModel>
     */
    public static function fromArray(mixed $data = null, Model|null $model = null): DtoCollection|static
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
            return static::fromEmpty(model: $model);
        }

        if (!is_assoc($data)) {
            return static::fromCollection($data, $model);
        }

        $data = static::fireEvent('prepareArray', $data, static::SET_CURRENT_DATA);

        [$dto, $arguments] = static::makeInstanceFromArray($data, $model);

        static::fireEvent('created', [], $dto, $arguments);
        static::fireEvent('fromArray', [], $dto, $arguments);

        $dto->log('createdFromArray', [], ms: static::endTime($start));

        return $dto;
    }

    /**
     * Create a new instance from an associative array
     *
     * @param  mixed|null  $data
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return static
     */
    public static function fromAssoc(mixed $data = null, Model|null $model = null): static
    {
        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } elseif (is_iterable($data)) {
            $data = is_array($data) ? $data : iterator_to_array($data);
        } else {
            $data = [];
        }

        if ($data && !is_assoc($data)) {
            throw new \InvalidArgumentException('Expected an associative array, got: ' . gettype($data));
        }

        return static::fromArray($data, $model);
    }

    /**
     * Dto constructor from empty
     *
     * @param  array<string, mixed>  $data
     * @param  TModel|Model|null  $model
     * @return static<TModel>
     */
    public static function fromEmpty(array $data = [], Model|null $model = null): static
    {
        $start = static::startTime();

        $data = static::fireEvent('prepareEmpty', $data, static::class);

        $data = static::fireEvent('creating', $data, static::SET_CURRENT_DATA);

        [$dto, $arguments] = static::makeInstanceFromArray($data, $model);

        static::fireEvent('created', [], $dto, $arguments);
        static::fireEvent('fromEmpty', [], $dto, $arguments);

        $dto->log('createdFromEmpty', [], ms: static::endTime($start));

        return $dto;
    }

    /**
     * Create a new instance from a string
     *
     * @param  string  $string
     * @param  TModel|Model|null  $model
     * @return DtoCollection<int, static<TModel>>|static<TModel>
     */
    public static function fromString(string $string, Model|null $model = null): DtoCollection|static
    {
        if (static::isJson($string)) {
            return static::fromJson($string, $model);
        } elseif (static::isSerialize($string)) {
            return static::fromSerialize($string, $model);
        } elseif (strlen($string) <= 1024 && class_exists($string)) {
            return static::fromClassString($string, $model);
        } elseif (strlen($string) <= 30000 && filter_var($string, FILTER_VALIDATE_URL) !== false) {
            return static::fromUrl($string, $model);
        }

        $dto = static::new($string, __model: $model);
        $dto::setImportType('string', $string, instance: $dto);
        return $dto;
    }

    public static function fromLineValues(string $line, string $separator = '|', Model|null $model = null): static
    {
        $string = explode($separator, $line);
        $dto = static::new(...$string, __model: $model);
        $dto::setImportType('LineValues', $string, args: compact('separator'), instance: $dto);
        return $dto;
    }

    public static function fromKeyValueLines(
        string $lines,
        string $lineSeparator = ':',
        string $nextLineSeparator = PHP_EOL,
        Model|null $model = null
    ): static {
        $assoc = [];
        foreach (explode($nextLineSeparator, $lines) as $line) {
            if (str_contains($line, $lineSeparator)) {
                [$key, $value] = explode($lineSeparator, $line, 2);
                $key = trim($key);
                $value = trim($value);
                if ($key !== '' && $value !== '') {
                    $assoc[$key] = $value;
                }
            }
        }
        $dto = $assoc ? static::fromAssoc($assoc, $model) : static::fromEmpty(model: $model);
        $dto::setImportType('KeyValueLines', $lines, args: compact('lineSeparator', 'nextLineSeparator'), instance: $dto);
        return $dto;
    }

    /**
     * Make an instance from a numeric value
     *
     * @param  int|float|string  $numeric
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return static
     */
    public static function fromNumeric(int|float|string $numeric, Model|null $model = null): static
    {
        $dto = static::new($numeric, __model: $model);
        $dto::setImportType('numeric', $numeric, instance: $dto);
        return $dto;
    }

    /**
     * Make an instance from a class string
     *
     * @param  string|null  $class
     * @param  TModel|Model|null  $model
     * @return \Bfg\Dto\Collections\DtoCollection<int, static<TModel>>|static<TModel>
     */
    public static function fromClassString(string|null $class, Model|null $model = null): DtoCollection|static
    {
        if (is_subclass_of($class, Request::class)) {
            return static::fromRequest($class, $model);
        } elseif (is_subclass_of($class, Dto::class)) {
            return static::fromDto($class, $model);
        }

        return static::fromContainer($class, $model);
    }

    /**
     * Make an instance from a container abstract
     *
     * @param  string  $abstract
     * @param  TModel|Model|null  $model
     * @return \Bfg\Dto\Collections\DtoCollection<int, static<TModel>>|static<TModel>
     */
    public static function fromContainer(string $abstract, Model|null $model = null): DtoCollection|static
    {
        return static::fromObject(app($abstract), $model);
    }
}

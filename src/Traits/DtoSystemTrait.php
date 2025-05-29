<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Attributes\DtoFromCache;
use Bfg\Dto\Attributes\DtoFromConfig;
use Bfg\Dto\Attributes\DtoFromRequest;
use Bfg\Dto\Attributes\DtoFromRoute;
use Bfg\Dto\Attributes\DtoItem;
use Bfg\Dto\Attributes\DtoName;
use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Dto;
use Bfg\Dto\Exceptions\DtoExtensionTypeNotFoundException;
use Bfg\Dto\Exceptions\DtoModelBindingFailException;
use Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException;
use Bfg\Dto\Exceptions\DtoValidationException;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use ReflectionParameter;

trait DtoSystemTrait
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<Dto|DtoCollection, iterable>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class(static::class, static::$source) implements CastsAttributes
        {
            /**
             * @param  class-string<Dto>  $class
             * @param  string|null  $source
             */
            public function __construct(
                protected string $class,
                protected string|null $source,
            ) {
            }

            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key]) || ! $attributes[$key]) {
                    return null;
                }

                $this->class = $this->class::discoverCastedDto($model, $key, $value, $attributes);

                return $this->class::fromAnything(tag_replace($attributes[$key], $model));
            }

            public function set($model, $key, $value, $attributes): array
            {
                if ($value instanceof Dto || $value instanceof Collection) {
                    return [$key => $this->source ?: $value->toJson(JSON_UNESCAPED_UNICODE)];
                } elseif (is_string($value)) {
                    return [$key => $value];
                }
                return [$key => $value];
            }
        };
    }

    /**
     * Make instance from prepared array data
     *
     * @param  array  $data
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return array
     * @throws \Bfg\Dto\Exceptions\DtoModelBindingFailException
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     * @throws \ReflectionException
     */
    protected static function makeInstanceFromArray(array $data, Model $model = null): array
    {
        $arguments = [];
        $rules = array_merge(static::$rules, static::rules());
        if ($rules) {
            $messages = array_merge(static::$ruleMessages, static::ruleMessages());
            $validator = Validator::make($data, $rules, $messages);
            if ($validator->fails()) {
                throw new DtoValidationException($validator);
            }
            $data = $validator->validated();
        }
        $created = [];
        $constructorParameters = static::getConstructorParameters();
        foreach ($constructorParameters as $parameter) {

            [$name, $value] = static::createNameValueFromProperty($parameter, $data, $model);

            if ($value === null) {
                $methodByDefault = 'default' . ucfirst(Str::camel($name));
                if (method_exists(static::class, $methodByDefault)) {
                    $value = static::$methodByDefault();
                }
            }

            $arguments[$name] = $value;
            $created[$name] = $name;
        }

        foreach (static::$extends as $key => $types) {

            if (array_key_exists($key, $arguments)) {
                continue;
            }

            $types = is_array($types) ? $types : explode('|', $types);

            [$name, $value] = static::createNameValueFromExtendedProperty($key, $types, $data, $model);

            if ($value === null) {
                $methodByDefault = 'default' . ucfirst(Str::camel($name));
                if (method_exists(static::class, $methodByDefault)) {
                    $value = static::$methodByDefault();
                }
            }

            $arguments[$name] = $value;
        }

        foreach (static::$encrypted as $key) {

            if (array_key_exists($key, $arguments)) {
                try {
                    $arguments[$key]
                        = static::currentEncrypter()->decrypt($arguments[$key]);
                } catch (\Throwable) {
                }
            }
        }

        foreach ($arguments as $key => $argument) {
            $arguments[$key] = is_null($argument) ? $argument : static::castAttribute($key, $argument, $arguments);
        }

        $arguments = static::fireEvent('creating', $arguments, static::SET_CURRENT_DATA);

        $extendedKeys = array_filter(array_keys(static::$extends), fn ($key) => ! isset($created[$key]));

        $argumentsToInstance = array_diff_key($arguments, array_flip($extendedKeys));

        $dto = new static(...$argumentsToInstance);

        if ((static::$allowDynamicProperties || ! count($constructorParameters)) && $data) {
            foreach ($data as $key => $value) {
                if (! array_key_exists($key, $argumentsToInstance) && ! in_array($key, $extendedKeys)) {
                    $dto->{$key} = $value;
                }
            }
        }

        static::$__parameters[static::class][spl_object_id($dto)]
            = array_intersect_key($arguments, array_flip($extendedKeys));

        foreach ($arguments as $key => $argument) {
            $dto::$__setWithoutCasting = true;
            $dto->set($key, $argument);
        }

        static::$__originals[static::class][spl_object_id($dto)] = $data;
        return [$dto, $arguments];
    }

    /**
     * Make property from extended property
     *
     * @param  string  $key
     * @param  array  $types
     * @param  array  $data
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return array
     * @throws \Bfg\Dto\Exceptions\DtoModelBindingFailException
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    protected static function createNameValueFromExtendedProperty(string $key, array $types, array $data, Model $model = null): array
    {
        $type = $types[0];
        if (! $type) {
            throw new DtoExtensionTypeNotFoundException();
        }
        $isBuiltin = in_array($type, ['string', 'int', 'float', 'bool', 'array', 'object', 'null', 'mixed', 'callable', 'iterable', 'false', 'true', 'resource']);
        $isNullable = in_array('null', $types);
        $hasCollection = in_array(Collection::class, $types);
        $hasArray = in_array('array', $types);


        [$nameInData, $notFoundKeys, $isOtherParam, $data] = static::detectAttributesForExtended($data, $key);

        if ($model) {
            if (! array_key_exists($nameInData, $data)) {

                $data[$nameInData] = $model->{$nameInData};
            }
        }

        if (! $isBuiltin && ! $isOtherParam && class_exists($type)) {

            if (is_subclass_of($type, Dto::class)) {
                $value = static::discoverDtoValue($hasCollection, $hasArray, $nameInData, $type, $data, $isNullable);
            } else {
                if (is_subclass_of($type, Model::class)) {
                    $value = static::discoverModelValue($nameInData, $type, $data, $isNullable);
                } else {
                    $value = static::discoverOtherValue($nameInData, $type, $data);
                }
            }
        } else {
            $value = array_key_exists($nameInData, $data)
                ? $data[$nameInData]
                : null;
        }

        if (! $isNullable && $value === null) {
            throw new DtoUndefinedArrayKeyException($nameInData . ($notFoundKeys ? ', ' . implode(', ', $notFoundKeys) : ''));
        }

        $value = static::castAttribute($key, $value, $data, $type);

        $value = static::fireEvent(['created', $key], $value, static::SET_CURRENT_DATA, $data, compact('key', 'types'));
        $value = static::transformAttribute($key, $value);

        return [$key, $value];
    }

    /**
     * Create name value from property
     *
     * @param  ReflectionParameter  $parameter
     * @param  array  $data
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return array
     * @throws \Bfg\Dto\Exceptions\DtoModelBindingFailException
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    protected static function createNameValueFromProperty(ReflectionParameter $parameter, array $data = [], Model $model = null): array
    {
        $type = $parameter->getType();
        $name = $parameter->getName();
        static::fireEvent(['creating', $name], null, $data, $parameter);

        [$type, $hasCollection, $hasArray] = static::detectType($type);
        [$nameInData, $notFoundKeys, $isOtherParam, $data] = static::detectAttributes($data, $parameter);

        if ($model) {
            if (! array_key_exists($nameInData, $data)) {

                $data[$nameInData] = $model->{$nameInData};
            }
        }

        $methodDefault = 'default' . ucfirst(Str::camel($name));
        if (
            $type->isBuiltin()
            && (
                ! array_key_exists($nameInData, $data)
                && ! $parameter->isDefaultValueAvailable()
            )
            && ! $type->allowsNull()
            && ! method_exists(static::class, $methodDefault)
        ) {
            throw new DtoUndefinedArrayKeyException(
                $nameInData . ($notFoundKeys ? ', ' . implode(', ', $notFoundKeys) : '')
            );
        }

        if (! $type->isBuiltin() && ! $isOtherParam) {
            $class = $classCollection = $type->getName();

            $attributes = $parameter->getAttributes(DtoItem::class);
            if (count($attributes)) {
                $attribute = $attributes[0]->newInstance();
                $class = $attribute->className;
            }

            if (is_subclass_of($class, Dto::class)) {
                $value = static::discoverDtoValue($hasCollection, $hasArray, $nameInData, $class, $data, $parameter->allowsNull(), $model, $classCollection);
            } else {
                if (is_subclass_of($class, Model::class)) {
                    $value = static::discoverModelValue($nameInData, $class, $data, $parameter->allowsNull());
                } else {
                    $value = static::discoverOtherValue($nameInData, $class, $data);
                }
            }
        } else {
            $value = array_key_exists($nameInData, $data)
                ? $data[$nameInData]
                : ($parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null);
        }
        $value = static::fireEvent(['created', $name], $value, static::SET_CURRENT_DATA, $data, $parameter);
        $value = static::transformAttribute($name, $value);
        if ($value === null && $parameter->isDefaultValueAvailable() && ! $parameter->allowsNull()) {
            $value = $parameter->getDefaultValue();
        }

        return [$name, $value];
    }

    /**
     * @param $type
     * @return mixed|\ReflectionIntersectionType|\ReflectionNamedType|\ReflectionUnionType
     */
    protected static function detectType(
        $type
    ): mixed {
        $hasCollection = false;
        $hasArray = false;
        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if (! $unionType->isBuiltin()) {
                    $class = $unionType->getName();
                    if (is_subclass_of($class, Dto::class)) {
                        $type = $unionType;
                        continue;
                    }
                    if (is_subclass_of($class, Collection::class) || $class === Collection::class) {
                        $hasCollection = true;
                    }
                } else {
                    if ($unionType->getName() === 'array') {
                        $hasArray = true;
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

        if ($type instanceof \ReflectionNamedType) {
            $class = $type->getName();
            if (is_subclass_of($class, Collection::class) || $class === Collection::class) {
                $hasCollection = true;
            } elseif ($class === 'array') {
                $hasArray = true;
            }
        }

        return [$type, $hasCollection, $hasArray];
    }

    protected static function detectAttributes(
        array $data,
        ReflectionParameter $parameter,
    ): array {
        $notFoundKeys = [];
        $isOtherParam = false;
        $nameInData = $parameter->getName();
        $attributes = $parameter->getAttributes(DtoName::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if (array_key_exists($instance->name, $data)) {
                $nameInData = $instance->name;
            } else {
                $notFoundKeys[] = $instance->name;
            }
        }
        $attributes = $parameter->getAttributes(DtoFromRoute::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if (! array_key_exists($nameInData, $data) || ! $data[$nameInData]) {
                $data[$nameInData] = request()->route($instance->name ?: $nameInData);
            }
            $isOtherParam = true;
        }
        $attributes = $parameter->getAttributes(DtoFromConfig::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if (! array_key_exists($nameInData, $data) || ! $data[$nameInData]) {
                $data[$nameInData] = config($instance->name ?: $nameInData);
            }
            $isOtherParam = true;
        }
        $attributes = $parameter->getAttributes(DtoFromRequest::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if (! array_key_exists($nameInData, $data) || ! $data[$nameInData]) {
                $data[$nameInData] = request()->__get($instance->name ?: $nameInData);
            }
            $isOtherParam = true;
        }
        $attributes = $parameter->getAttributes(DtoFromCache::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if (! array_key_exists($nameInData, $data) || ! $data[$nameInData]) {
                $data[$nameInData] = Cache::get($instance->name ?: $nameInData);
            }
            $isOtherParam = true;
        }

        return [$nameInData, $notFoundKeys, $isOtherParam, $data];
    }

    protected static function detectAttributesForExtended(
        array $data,
        string $nameInData,
    ): array {
        $property = (new \ReflectionProperty(static::class, 'extends'));
        $notFoundKeys = [];
        $isOtherParam = false;
        $key = $nameInData;
        $attributes = $property->getAttributes(DtoName::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance->from === $key) {
                if (array_key_exists($instance->name, $data)) {
                    $nameInData = $instance->name;
                } else {
                    $notFoundKeys[] = $instance->name;
                }
            }
        }
        $attributes = $property->getAttributes(DtoFromRoute::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance->from === $key) {
                if (! array_key_exists($nameInData, $data) || ! $data[$nameInData]) {
                    $data[$nameInData] = request()->route($instance->name ?: $nameInData);
                }
                $isOtherParam = true;
            }
        }
        $attributes = $property->getAttributes(DtoFromConfig::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance->from === $key) {
                if (! array_key_exists($nameInData, $data) || ! $data[$nameInData]) {
                    $data[$nameInData] = config($instance->name ?: $nameInData);
                }
                $isOtherParam = true;
            }
        }
        $attributes = $property->getAttributes(DtoFromRequest::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance->from === $key) {
                if (! array_key_exists($nameInData, $data) || ! $data[$nameInData]) {
                    $data[$nameInData] = request()->__get($instance->name ?: $nameInData);
                }
                $isOtherParam = true;
            }
        }
        $attributes = $property->getAttributes(DtoFromCache::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance->from === $key) {
                if (! array_key_exists($nameInData, $data) || ! $data[$nameInData]) {
                    $data[$nameInData] = Cache::get($instance->name ?: $nameInData);
                }
                $isOtherParam = true;
            }
        }

        return [$nameInData, $notFoundKeys, $isOtherParam, $data];
    }

    /**
     * @param  string  $nameInData
     * @param  string  $class
     * @param  array  $data
     * @param  string|null  $dtoModel
     * @return mixed
     */
    protected static function discoverOtherValue(
        string $nameInData,
        string $class,
        array $data,
        string|null $dtoModel = null,
    ): mixed {
        if (! is_subclass_of($class, Carbon::class) && $class !== Carbon::class) {
            if (is_subclass_of($class, Collection::class) || $class === Collection::class) {
                $value = new $class($data[$nameInData]);
            } elseif (
                (is_subclass_of($class, FormRequest::class) || $class === FormRequest::class)
                || (is_subclass_of($class, Request::class) || $class === Request::class)
            ) {
                $value = isset($data[$nameInData]) ? new $class($data[$nameInData]) : app($class);
            } else {
                $value = $data[$nameInData] ?? null;
                if (! $value && ! enum_exists($class)) {
                    $value = app($class);
                }
            }
        } else {
            $value = $data[$nameInData] ?? null;

            if (! $value instanceof Carbon) {

                if (is_numeric($value)) {
                    $value = Carbon::createFromTimestamp($value);
                } elseif ($value) {
                    $value = Carbon::parse($value);
                }
            }
        }

        return $value;
    }

    /**
     * Discover model value
     *
     * @param  string  $nameInData
     * @param  Model|string  $class
     * @param  array  $data
     * @param  bool  $allowsNull
     * @return mixed
     * @throws \Bfg\Dto\Exceptions\DtoModelBindingFailException
     */
    protected static function discoverModelValue(
        string $nameInData,
        Model|string $class,
        array $data,
        bool $allowsNull,
    ): mixed {
        $val = $data[$nameInData] ?? null;
        if (is_numeric($val)) {
            $value = $class::find($val);
            if (! $value && ! $allowsNull) {
                throw new DtoModelBindingFailException($class, 'id', $val);
            }
        } elseif (is_string($val)) {
            $exploded = explode(':', $val);
            if (count($exploded) === 2) {
                $value = $class::where($exploded[0], $exploded[1])->first();
                if (! $value && ! $allowsNull) {
                    throw new DtoModelBindingFailException($class, $exploded[0], $exploded[1]);
                }
            } else {
                $firstFieldFromFillable = (new $class())->getFillable()[0] ?? 'id';
                $value = $class::where($firstFieldFromFillable, $val)->first();
                if (! $value && ! $allowsNull) {
                    throw new DtoModelBindingFailException($class, $firstFieldFromFillable, $val);
                }
            }
        } elseif (is_array($val)) {
            $value = new $class($val);
        } else {
            $value = $val;
        }

        return $value;
    }

    /**
     * Discover dto value
     *
     * @param  bool  $hasCollection
     * @param  bool  $hasArray
     * @param  string  $nameInData
     * @param  Dto|string  $class
     * @param  array  $data
     * @param  bool  $allowsNull
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @param  string|null  $classCollection
     * @return mixed
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    protected static function discoverDtoValue(
        bool $hasCollection,
        bool $hasArray,
        string $nameInData,
        Dto|string $class,
        array $data,
        bool $allowsNull,
        Model $model = null,
        string|null $classCollection = null,
    ): mixed {
        $classCollection = is_subclass_of($classCollection, Collection::class)
            ? $classCollection
            : DtoCollection::class;

        $namedData = array_key_exists($nameInData, $data)
            ? $data[$nameInData]
            : ($model ? $model->{$nameInData} : []);

        if ($hasCollection) {
            if (is_string($namedData)) {
                if (static::isSerialize($namedData)) {
                    $value = unserialize($namedData);
                } elseif (static::isJson($namedData)) {
                    $value = new $classCollection(json_decode($namedData, true));
                }
            } else {
                $value = $allowsNull && ! $namedData
                    ? null
                    : new $classCollection();
                if ($value && $namedData) {
                    foreach ($namedData as $item) {
                        $value->push($class::fromAnything($item));
                    }
                }
            }
        } elseif ($hasArray) {
            $value = $allowsNull ? null : [];
            foreach ($namedData as $item) {
                $value[] = $class::fromAnything($item);
            }
        } else {
            $value = $namedData
                ? ($namedData instanceof Collection ? $class::fromAnything($namedData->first()) : $class::fromAnything($namedData))
                : null;
        }

        return $value ?? null;
    }

    /**
     * Custom value transformation
     *
     * @param  string  $key
     * @param $value
     * @return mixed
     */
    protected static function transformAttribute(string $key, $value): mixed
    {
        return $value;
    }

    /**
     * Get dto constructor parameters
     *
     * @return \ReflectionParameter[]|array<int, \ReflectionParameter>
     */
    public static function getConstructorParameters(): array
    {
        if (isset(static::$__constructorParameters[static::class])) {
            return static::$__constructorParameters[static::class];
        }
        try {
            return static::$__constructorParameters[static::class] = static::reflection()
                ->getMethod('__construct')
                ?->getParameters() ?: [];
        } catch (\ReflectionException) {
        }

        return static::$__constructorParameters[static::class] = [];
    }

    protected static function isJson(string $data): bool
    {
        $data = trim($data);

        if ($data === '') {
            return false;
        }
        return !! preg_match('/^(?:\{.*\}|\[.*\])$/s', $data);
    }

    protected static function isSerialize(string $data): bool
    {
        $data = trim($data);

        if ($data === '') {
            return false;
        }

        if (
            preg_match('/^(s|a|O|b|i|d|N):/', $data) &&
            (str_ends_with($data, ';') || str_ends_with($data, '}'))
        ) {
            return true;
        }

        return false;
    }

    protected static function makeValueByType(string $type, array $types = []): mixed
    {
        $value = null;

        if ($type === 'string' || in_array('string', $types)) {
            $value = "";
        } elseif ($type === 'int' || in_array('int', $types)) {
            $value = 0;
        } elseif ($type === 'float' || in_array('float', $types)) {
            $value = 0.0;
        } elseif ($type === 'bool' || in_array('bool', $types)) {
            $value = false;
        } elseif ($type === 'array' || in_array('array', $types)) {
            $value = [];
        } elseif ($type === 'object' || in_array('object', $types)) {
            $value = new \stdClass();
        }

        return $value;
    }

    /**
     * Trap for model
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected static function configureModel(Model $model): Model
    {
        return $model;
    }

    /**
     * The validation rules in the method
     *
     * @return array
     */
    protected static function rules(): array
    {
        return [];
    }

    /**
     * The validation rule messages in the method
     *
     * @return array
     */
    protected static function ruleMessages(): array
    {
        return [];
    }

    /**
     * The headers in the method for from get request
     *
     * @return array
     */
    protected static function httpHeaders(): array
    {
        return [];
    }

    /**
     * Client creator for http requests
     *
     * @return PendingRequest
     */
    protected static function httpClient(): PendingRequest
    {
        return Http::createPendingRequest();
    }

    /**
     * @param  array|string|null  $data
     * @return array|string|null
     */
    protected static function httpData(array|string|null $data): array|string|null
    {
        return $data;
    }

    /**
     * Discover the class name of the casted DTO.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return class-string<Dto>
     */
    public static function discoverCastedDto(Model $model, string $key, mixed $value, array $attributes): string
    {
        return static::class;
    }
}

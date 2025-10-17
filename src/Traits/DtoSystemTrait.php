<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Attributes\DtoAuthenticatedUser;
use Bfg\Dto\Attributes\DtoFromCache;
use Bfg\Dto\Attributes\DtoFromConfig;
use Bfg\Dto\Attributes\DtoFromRequest;
use Bfg\Dto\Attributes\DtoFromRoute;
use Bfg\Dto\Attributes\DtoItem;
use Bfg\Dto\Attributes\DtoMapApi;
use Bfg\Dto\Attributes\DtoMapFrom;
use Bfg\Dto\Attributes\DtoMutateFrom;
use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Dto;
use Bfg\Dto\Exceptions\DtoExtensionTypeNotFoundException;
use Bfg\Dto\Exceptions\DtoModelBindingFailException;
use Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException;
use Bfg\Dto\Exceptions\DtoValidationException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use ReflectionParameter;

trait DtoSystemTrait
{
    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->toArray());
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return $this
     */
    public function setModel(Model|null $model): static
    {
        if ($model) {
            static::$__models[static::class][spl_object_id($this)] = $model;
        }
        return $this;
    }

    /**
     * Set the default callback for a field
     *
     * @param  string  $field
     * @param  callable|mixed  $value
     * @return void
     */
    public static function default(string $field, mixed $value): void
    {
        static::$__defaultCallbacks[static::class][$field] = $value;
    }

    /**
     * Set import type for the DTO
     *
     * @param  string  $type
     * @param  mixed|null  $source
     * @param  bool  $manual
     * @param  array  $args
     * @param  \Bfg\Dto\Dto|null  $instance
     * @return void
     */
    public static function setImportType(
        string $type,
        mixed $source = null,
        bool $manual = false,
        array $args = [],
        Dto|null $instance = null,
    ): void {
        static::$__importType[static::class]
            = compact('type', 'source', 'manual', 'args', 'instance');
        if ($instance) {
            $instanceId = spl_object_id($instance);
            static::$__importType[$instanceId]
                = static::$__importType[static::class];
        }
    }

    /**
     * Get an import type for the DTO
     *
     * @return array{type: string, source: mixed|null, manual: bool, args: array<string, mixed>}
     */
    public static function getImportType(Dto|null $instance = null): array
    {
        $instanceId = $instance ? spl_object_id($instance) : null;

        if (
            $instanceId
            && isset(static::$__importType[$instanceId])
        ) {
            return static::$__importType[$instanceId];
        }

        return (static::$__importType[static::class] ?? [
            'type' => 'json',
            'source' => null,
            'manual' => false,
            'args' => [],
            'instance' => $instance,
        ]);
    }

    /**
     * Prepare data for DTO instance creation
     *
     * @param  array  $data
     * @return array
     */
    protected static function prepareData(array $data): array
    {
        return $data;
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
    protected static function makeInstanceFromArray(array $data, Model|null $model = null): array
    {
        $arguments = [];
        $rules = array_merge(static::$dtoValidateRules, static::rules());
        if ($rules) {
            $messages = array_merge(static::$dtoValidateMessages, static::ruleMessages());
            $validator = Validator::make($data, $rules, $messages);
            if ($validator->fails()) {
                throw new DtoValidationException($validator);
            }
            $data = $validator->validated();
        }
        $data = static::prepareData($data);

        foreach (static::$extends as $key => $types) {

            if (array_key_exists($key, $arguments)) {
                continue;
            }

            $types = is_array($types) ? $types : explode('|', $types);

            [$name, $value] = static::createNameValueFromExtendedProperty($key, $types, $data, $model);
            $arguments[$name] = $value;
            $data[$name] = $value;
        }

        $created = [];
        $constructorParameters = static::getConstructorParameters();
        foreach ($constructorParameters as $parameter) {

            [$name, $value] = static::createNameValueFromProperty($parameter, $data, $model);
            $arguments[$name] = $value;
            $created[$name] = $name;
            $data[$name] = $value;
        }

        foreach (static::$dtoEncrypted as $key) {

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

        $notSetArgs = array_diff_key($data, $argumentsToInstance);

        $setProperty = [];

        foreach ($notSetArgs as $key => $value) {
            if (! in_array($key, $extendedKeys) && property_exists($dto, $key)) {
                $dto->set($key, $value);
                $setProperty[] = $key;
            }
        }

        if ((static::$allowDynamicProperties || ! count($constructorParameters)) && $data) {
            foreach ($data as $key => $value) {
                if (
                    ! array_key_exists($key, $argumentsToInstance)
                    && ! in_array($key, $extendedKeys)
                    && ! in_array($key, $setProperty)
                ) {
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
        $dto->setModel($model);

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
        $hasCollection = in_array(DtoCollection::class, $types);
        $hasArray = in_array('array', $types);

        [$nameInData, $notFoundKeys, $isOtherParam, $data] = static::detectAttributesForExtended($data, $key);
        $valueInDataExists = dto_data_exists($data, $nameInData);

        if ($model) {
            if (! $valueInDataExists) {
                $data[$key] = data_get($model, $nameInData);
                $valueInDataExists = true;
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
            $value = $valueInDataExists
                ? data_get($data, $nameInData)
                : null;
        }

        $value = $value === null ? static::generateDefault($key, $data) : $value;

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
        [$type, $hasCollection, $hasArray, $allowNull, $typeNames] = static::detectType($type);
        [$nameInData, $notFoundKeys, $isOtherParam, $data] = static::detectAttributes($data, $parameter);
        $valueInDataExists = dto_data_exists($data, $nameInData);

        if ($model) {
            if (! $valueInDataExists) {
                $data[$parameter->getName()] = data_get($model, $nameInData);
                $valueInDataExists = true;
            }
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
            $value = $valueInDataExists
                ? data_get($data, $nameInData)
                : ($parameter->isDefaultValueAvailable()
                    ? $parameter->getDefaultValue()
                    : ($allowNull ? null : static::makeValueByType($type->getName(), $typeNames)));
        }
        $value = $value === null ? static::generateDefault($name, $data) : $value;
        if (
            $type->isBuiltin()
            && (
                ! $valueInDataExists
                && ! $parameter->isDefaultValueAvailable()
            )
            && ! $allowNull
            && $value === null
        ) {
            throw new DtoUndefinedArrayKeyException(
                $nameInData . ($notFoundKeys ? ', ' . implode(', ', $notFoundKeys) : '')
            );
        }
        $value = static::fireEvent(['created', $name], $value, static::SET_CURRENT_DATA, $data, $parameter);
        $value = static::transformAttribute($name, $value);
        if ($value === null && $parameter->isDefaultValueAvailable() && ! $parameter->allowsNull()) {
            $value = $parameter->getDefaultValue();
        }

        return [$name, $value];
    }

    /**
     * Generate default value for a field
     *
     * @param  string  $name
     * @param  array  $data
     * @return mixed
     */
    protected static function generateDefault(string $name, array $data = []): mixed
    {
        if (isset(static::$__defaultCallbacks[static::class][$name])) {
            $cb = static::$__defaultCallbacks[static::class][$name];
            return is_callable($cb) ? call_user_func($cb, $data) : $cb;
        } else {
            $methodByDefault = 'default' . Str::studly($name);
            if (method_exists(static::class, $methodByDefault)) {
                return static::$methodByDefault($data);
            }
        }
        return $data[$name] ?? null;
    }

    /**
     * Check if a default value generator exists for a field
     *
     * @param  string  $name
     * @return bool
     */
    protected static function hasGenerateDefault(string $name): bool
    {
        return isset(static::$__defaultCallbacks[static::class][$name])
            || method_exists(static::class, 'default' . Str::studly($name));
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
        $allowNull = false;
        $typeNames = [];
        if ($type instanceof \ReflectionUnionType) {
            $allowNull = $type->allowsNull();
            foreach ($type->getTypes() as $unionType) {
                if ($unionType instanceof \ReflectionNamedType) {
                    $typeNames[] = $unionType->getName();
                }
            }
            foreach ($type->getTypes() as $unionType) {
                if (! $unionType->isBuiltin()) {
                    $class = $unionType->getName();
                    if (is_subclass_of($class, Dto::class)) {
                        $type = $unionType;
                        continue;
                    }
                    if (is_subclass_of($class, DtoCollection::class) || $class === DtoCollection::class) {
                        $hasCollection = true;
                    }
                } else {
                    if ($unionType->getName() === 'array') {
                        $hasArray = true;
                    }
                }
            }
        } else {
            if ($type instanceof \ReflectionIntersectionType) {
                $allowNull = $type->allowsNull();
                foreach ($type->getTypes() as $intersectionType) {
                    if ($intersectionType instanceof \ReflectionNamedType) {
                        $typeNames[] = $intersectionType->getName();
                    }
                }
                $type = $typeNames[0] ?? null;
            } elseif ($type instanceof \ReflectionNamedType) {
                $allowNull = $type->allowsNull();
                $typeNames[] = $type->getName();
            } else {
                throw new \InvalidArgumentException('Unsupported type: ' . get_class($type));
            }
        }

        if ($type instanceof \ReflectionUnionType) {
            $allowNull = $type->allowsNull();
            foreach ($type->getTypes() as $unionType) {
                $type = $unionType;
                break;
            }
        }

        if ($type instanceof \ReflectionNamedType) {
            $class = $type->getName();
            if (is_subclass_of($class, DtoCollection::class) || $class === DtoCollection::class) {
                $hasCollection = true;
            } elseif ($class === 'array') {
                $hasArray = true;
            }
        }

        return [$type, $hasCollection, $hasArray, $allowNull, $typeNames];
    }

    protected static function detectAttributes(
        array $data,
        ReflectionParameter $parameter,
    ): array {
        $notFoundKeys = [];
        $isOtherParam = false;
        $originalParameterName = $parameter->getName();
        $nameInData = $parameter->getName();
        $attributes = $parameter->getAttributes(DtoMapFrom::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            $valueInDataExists = dto_data_exists($data, $instance->name);
            if ($valueInDataExists) {
                $nameInData = $instance->name;
            } else {
                $notFoundKeys[] = $instance->name;
            }
        }
        if ($nameInData === $originalParameterName) {
            $attributes = $parameter->getAttributes(DtoMapApi::class);
            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                if ($instance instanceof DtoMapApi) {
                    $nameInData = Str::snake($nameInData);
                }
            }
        }
        $attributes = $parameter->getAttributes(DtoMutateFrom::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof DtoMutateFrom) {
                $instance->cb = is_string($instance->cb) && ! is_callable($instance->cb)
                    ? [static::class, $instance->cb] : $instance->cb;
                if (is_callable($instance->cb)) {
                    $data[$nameInData] = call_user_func(
                        $instance->cb,
                        $data[$nameInData] ?? ($data[$originalParameterName] ?? null)
                    );
                } else {
                    throw new \InvalidArgumentException(
                        'The callback for DtoMutateFrom must be callable.'
                    );
                }
            }
        }
        $attributes = $parameter->getAttributes(DtoFromRoute::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            $data[$nameInData] = request()->route(
                $instance->name ?: $nameInData
            );
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
        $attributes = $property->getAttributes(DtoMapFrom::class);
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
        if ($key === $nameInData) {
            $attributes = $property->getAttributes(DtoMapApi::class);
            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                if ($instance->from === $key) {
                    if ($instance instanceof DtoMapApi) {
                        $nameInData = Str::snake($nameInData);
                    }
                }
            }
        }
        $attributes = $property->getAttributes(DtoMutateFrom::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof DtoMutateFrom) {
                if ($instance->from === $key) {
                    $instance->cb = is_string($instance->cb) && !is_callable($instance->cb)
                        ? [static::class, $instance->cb] : $instance->cb;
                    if (is_callable($instance->cb)) {
                        $data[$nameInData] = call_user_func(
                            $instance->cb,
                            $data[$nameInData] ?? ($data[$key] ?? null)
                        );
                    } else {
                        throw new \InvalidArgumentException(
                            'The callback for DtoMutateFrom must be callable.'
                        );
                    }
                }
            }
        }
        $attributes = $property->getAttributes(DtoFromRoute::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance->from === $key) {
                $data[$nameInData] = request()->route($instance->name ?: $nameInData);
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
        $attributes = $property->getAttributes(DtoAuthenticatedUser::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            $data[$key] = auth($instance->guard)->user();
            $isOtherParam = true;
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
        $valueInDataExists = dto_data_exists($data, $nameInData);
        $dataValue = data_get($data, $nameInData);

        if (is_subclass_of($class, \BackedEnum::class)) {
            if ($valueInDataExists) {
                if ($dataValue instanceof $class) {
                    $value = $dataValue;
                } else {
                    $value = $class::tryFrom($dataValue);
                    if (! $value) {
                        $value = $class::from($dataValue);
                    }
                }
            } else {
                $value = null;
            }
        } elseif (! is_subclass_of($class, Carbon::class) && $class !== Carbon::class) {
            if (is_subclass_of($class, DtoCollection::class) || $class === DtoCollection::class) {
                $value = new $class($dataValue);
            } elseif (
                (is_subclass_of($class, FormRequest::class) || $class === FormRequest::class)
                || (is_subclass_of($class, Request::class) || $class === Request::class)
            ) {
                $value = $valueInDataExists ? new $class($dataValue) : app($class);
            } else {
                $value = $dataValue;
                if (! $value && ! enum_exists($class)) {
                    $value = app($class);
                }
            }
        } else {
            $value = $dataValue;

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
        $val = data_get($data, $nameInData);
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
        $classCollection = is_subclass_of($classCollection, DtoCollection::class) || $class === DtoCollection::class
            ? $classCollection
            : DtoCollection::class;
        $valueInDataExists = dto_data_exists($data, $nameInData);
        $namedData = $valueInDataExists
            ? data_get($data, $nameInData)
            : ($model ? data_get($model, $nameInData) : null);

        if ($hasCollection) {
            if (is_string($namedData)) {
                if (static::isSerialize($namedData)) {
                    $value = unserialize($namedData);
                } elseif (static::isJson($namedData)) {
                    $value = new $classCollection(json_decode($namedData, true));
                }
            } else {
                if (is_iterable($namedData)) {
                    $value = new $classCollection();
                    foreach ($namedData as $item) {
                        $value->push($class::from($item, $model));
                    }
                } else {
                    if ($namedData) {
                        $value = new $classCollection();
                        $value->push($class::from($namedData, $model));
                    } else {
                        $value = $allowsNull ? null : new $classCollection();
                    }
                }
            }
        } elseif ($hasArray) {
            $value = $allowsNull ? null : [];
            if (is_iterable($namedData)) {
                foreach ($namedData as $item) {
                    $value[] = $class::from($item, $model);
                }
            }
        } else {
            $value = $allowsNull && ! $namedData ? null : $class::from($namedData, $model);
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
     * @return array<int, \ReflectionParameter>
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

    /**
     * Find a constructor parameter by its name
     *
     * @param  non-empty-string  $name
     * @return \ReflectionParameter|null
     */
    public static function findConstructorParameter(string $name): ReflectionParameter|null
    {
        $parameters = static::getConstructorParameters();
        foreach ($parameters as $parameter) {
            if ($parameter->getName() === $name) {
                return $parameter;
            }
        }
        return null;
    }

    public static function isJson(string $data): bool
    {
        $data = trim($data);

        if ($data === '') {
            return false;
        }
        return !! preg_match('/^(?:\{.*}|\[.*])$/s', $data);
    }

    public static function isSerialize(string $data): bool
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
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected static function configureModel(Model|null $model): Model|null
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
     * @return array|string|null
     */
    protected static function httpData(): array|string|null
    {
        return [];
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

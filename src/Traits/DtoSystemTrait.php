<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Attributes\DtoFromCache;
use Bfg\Dto\Attributes\DtoFromConfig;
use Bfg\Dto\Attributes\DtoFromRequest;
use Bfg\Dto\Attributes\DtoFromRoute;
use Bfg\Dto\Attributes\DtoName;
use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Dto;
use Bfg\Dto\Exceptions\DtoModelBindingFailException;
use Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException;
use Bfg\Dto\Exceptions\DtoValidationException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use ReflectionParameter;

trait DtoSystemTrait
{
    /**
     * Make instance from prepared array data
     *
     * @param  array  $data
     * @return array
     * @throws \Bfg\Dto\Exceptions\DtoModelBindingFailException
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     * @throws \Bfg\Dto\Exceptions\DtoValidationException
     */
    protected static function makeInstanceFromArray(array $data): array
    {
        $arguments = [];
        $rules = array_merge(static::$rules, static::rules());
        if ($rules) {
            $messages = array_merge(static::$ruleMessages, static::ruleMessages());
            $validator = Validator::make($data, $rules, $messages);
            if ($validator->fails()) {
                throw new DtoValidationException($validator);
            }
        }

        foreach (static::getConstructorParameters() as $parameter) {

            [$name, $value] = static::createNameValueFromProperty($parameter, $data);

            $arguments[$name] = $value;
        }

        foreach ($arguments as $key => $argument) {
            $arguments[$key] = static::castAttribute($key, $argument, $arguments);
        }

        $arguments = static::fireEvent('creating', $arguments, static::SET_CURRENT_DATA);

        $dto = new static(...$arguments);

        foreach ($arguments as $key => $argument) {
            $dto::$__setWithoutCasting = true;
            $dto->set($key, $argument);
        }

        static::$__originals[static::class][spl_object_id($dto)] = $dto->vars();

        return [$dto, $arguments];
    }

    /**
     * Create name value from property
     *
     * @param  ReflectionParameter  $parameter
     * @param  array  $data
     * @return array
     * @throws \Bfg\Dto\Exceptions\DtoModelBindingFailException
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    protected static function createNameValueFromProperty(ReflectionParameter $parameter, array $data = []): array
    {
        $type = $parameter->getType();
        $name = $parameter->getName();
        static::fireEvent(['creating', $name], null, $data, $parameter);

        [$type, $hasCollection, $hasArray] = static::detectType($type);
        [$nameInData, $notFoundKeys, $isOtherParam, $data] = static::detectAttributes($data, $parameter);

        if ($type->isBuiltin() && ! array_key_exists($nameInData, $data) && ! $type->allowsNull()) {
            throw new DtoUndefinedArrayKeyException($nameInData . ($notFoundKeys ? ', ' . implode(', ', $notFoundKeys) : ''));
        }

        if (! $type->isBuiltin() && ! $isOtherParam) {
            $class = $type->getName();

            if (is_subclass_of($class, Dto::class)) {
                $value = static::discoverDtoValue($hasCollection, $hasArray, $nameInData, $class, $data, $parameter);
            } else {
                if (is_subclass_of($class, Model::class)) {
                    $value = static::discoverModelValue($nameInData, $class, $data, $parameter);
                } else {
                    $value = static::discoverOtherValue($nameInData, $class, $data);
                }
            }
        } else {
            $value = $data[$nameInData] ?? null;
        }

        $value = static::fireEvent(['created', $name], $value, static::SET_CURRENT_DATA, $data, $parameter);
        $value = static::transformAttribute($name, $value);

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
            $data[$nameInData] = request()->route($instance->name ?: $nameInData);
            $isOtherParam = true;
        }
        $attributes = $parameter->getAttributes(DtoFromConfig::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            $data[$nameInData] = config($instance->name ?: $nameInData);
            $isOtherParam = true;
        }
        $attributes = $parameter->getAttributes(DtoFromRequest::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            $data[$nameInData] = request()->__get($instance->name ?: $nameInData);
            $isOtherParam = true;
        }
        $attributes = $parameter->getAttributes(DtoFromCache::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            $data[$nameInData] = Cache::get($instance->name ?: $nameInData);
            $isOtherParam = true;
        }

        return [$nameInData, $notFoundKeys, $isOtherParam, $data];
    }

    /**
     * @param  string  $nameInData
     * @param  string  $class
     * @param  array  $data
     * @return mixed
     */
    protected static function discoverOtherValue(
        string $nameInData,
        string $class,
        array $data,
    ): mixed {
        if (! is_subclass_of($class, Carbon::class) && $class !== Carbon::class) {
            if (is_subclass_of($class, Collection::class) || $class === Collection::class) {
                $value = new DtoCollection($data[$nameInData]);
            } else if (
                (is_subclass_of($class, FormRequest::class) || $class === FormRequest::class)
                || (is_subclass_of($class, Request::class) || $class === Request::class)
            ) {
                $value = isset($data[$nameInData]) ? new $class($data[$nameInData]) : app($class);
            } else {
                $value = app($class);
            }
        } else {
            $value = $data[$nameInData] ?? null;

            if (! $value instanceof Carbon) {

                if (is_numeric($value)) {
                    $value = Carbon::createFromTimestamp($value);
                } else {
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
     * @param  \ReflectionParameter  $parameter
     * @return mixed
     * @throws \Bfg\Dto\Exceptions\DtoModelBindingFailException
     */
    protected static function discoverModelValue(
        string $nameInData,
        Model|string $class,
        array $data,
        ReflectionParameter $parameter,
    ): mixed {
        $val = $data[$nameInData] ?? null;
        if (is_numeric($val)) {
            $value = $class::find($val);
            if (! $value && ! $parameter->allowsNull()) {
                throw new DtoModelBindingFailException($class, 'id', $val);
            }
        } else if (is_string($val)) {
            $exploded = explode(':', $val);
            if (count($exploded) === 2) {
                $value = $class::where($exploded[0], $exploded[1])->first();
                if (! $value && ! $parameter->allowsNull()) {
                    throw new DtoModelBindingFailException($class, $exploded[0], $exploded[1]);
                }
            } else {
                $firstFieldFromFillable = (new $class)->getFillable()[0] ?? 'id';
                $value = $class::where($firstFieldFromFillable, $val)->first();
                if (! $value && ! $parameter->allowsNull()) {
                    throw new DtoModelBindingFailException($class, $firstFieldFromFillable, $val);
                }
            }
        } else if (is_array($val)) {
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
     * @param  \ReflectionParameter  $parameter
     * @return mixed
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    protected static function discoverDtoValue(
        bool $hasCollection,
        bool $hasArray,
        string $nameInData,
        Dto|string $class,
        array $data,
        ReflectionParameter $parameter,
    ): mixed {
        if ($hasCollection) {
            $value = new DtoCollection();
            foreach ($data[$nameInData] ?? [] as $item) {
                $value->push($class::fromArray($item));
            }
        } else if ($hasArray) {
            $value = $parameter->allowsNull() ? null : [];
            foreach ($data[$nameInData] ?? [] as $item) {
                $value[] = $class::fromArray($item);
            }
        } else {
            $value = isset($data[$nameInData]) ? $class::fromArray($data[$nameInData]) : null;
        }

        return $value;
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
     * @return \ReflectionParameter[]|array
     */
    protected static function getConstructorParameters(): array
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
        return preg_match('/^(?:\{.*\}|\[.*\])$/s', $data);
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

    protected static function makeValueByType(string $type): mixed
    {
        $value = null;

        if ($type === 'string') {
            $value = "";
        } else if ($type === 'int') {
            $value = 0;
        } else if ($type === 'float') {
            $value = 0.0;
        } else if ($type === 'bool') {
            $value = false;
        } else if ($type === 'array') {
            $value = [];
        } else if ($type === 'object') {
            $value = new \stdClass();
        }

        return $value;
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
}

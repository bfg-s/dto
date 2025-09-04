<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Default\ExplainDto;
use Bfg\Dto\Default\ReflectionDto;
use Bfg\Dto\Dto;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionClass;

trait DtoReflectionTrait
{
    /**
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public function explain(): ExplainDto
    {
        return ExplainDto::fromArray([
            'name' => static::class,
            'ver' => static::$dtoVersion,
            'logsIsEnabled' => static::$dtoLogsEnabled,
            'meta' => static::$__meta[static::class][spl_object_id($this)] ?? [],
            'properties' => collect(static::getConstructorParameters())->map(function ($parameter) {

                if ($parameter->getType() instanceof \ReflectionUnionType) {
                    $types = [];
                    foreach ($parameter->getType()->getTypes() as $type) {
                        $types[] = $type->getName();
                    }
                }
                return [
                    'name' => $parameter->getName(),
                    'casting' => static::$dtoCast[$parameter->getName()] ?? null,
                    'type' => $types ?? $parameter->getType()?->getName(),
                    'default' => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                    'nullable' => $parameter->allowsNull(),
                    'isEncrypted' => in_array($parameter->getName(), static::$dtoEncrypted),
                    'isHidden' => in_array($parameter->getName(), static::$dtoHidden),
                    'rule' => static::$dtoValidateRules[$parameter->getName()] ?? null,
                    'value' => $this->{$parameter->getName()},
                ];
            })->merge(collect(static::$extends)->map(function (string|array $types, string $key) {
                $types = is_array($types) ? $types : explode('|', $types);

                return [
                    'name' => $key,
                    'casting' => static::$dtoCast[$key] ?? null,
                    'type' => $types,
                    'default' => null,
                    'nullable' => in_array('null', $types),
                    'isEncrypted' => in_array($key, static::$dtoEncrypted),
                    'isHidden' => in_array($key, static::$dtoHidden),
                    'rule' => static::$dtoValidateRules[$key] ?? null,
                    'value' => static::$__parameters[static::class][spl_object_id($this)][$key] ?? null,
                ];
            }))->toArray(),
            'computed' => collect(get_class_methods($this))->filter(function ($method) {
                return !in_array($method, [
                    '__construct', '__destruct', '__get', '__set', '__isset', '__unset', '__sleep', '__wakeup',
                    '__serialize', '__unserialize', '__clone', '__toString', '__invoke', '__set_state', '__clone',
                    '__debugInfo', '__serialize', '__unserialize', '__sleep', '__wakeup'
                ]) && ! method_exists(Dto::class, $method)
                    && ! str_starts_with($method, 'with')
                    && ! str_starts_with($method, 'default')
                    && ! str_starts_with($method, 'source')
                    && ! str_starts_with($method, 'lazy');
            })->values()->toArray(),
            'with' => collect(get_class_methods($this))->filter(function ($method) {
                return str_starts_with($method, 'with');
            })->values()->toArray(),
        ]);
    }

    public function vars(): array
    {
        $reflection = static::reflection();
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        $vars = [];
        foreach ($properties as $property) {
            if (!$property->isStatic()) {
                $vars[$property->getName()] = isset($this->{$property->getName()}) ? $this->{$property->getName()} : null;
            }
        }
        foreach (static::$extends as $key => $types) {
            $vars[$key] = static::$__parameters[static::class][spl_object_id($this)][$key] ?? null;
        }
        foreach (get_object_vars($this) as $key => $value) {
            if (! array_key_exists($key, $vars)) {
                $vars[$key] = $value;
            }
        }
        return static::$__vars[static::class][spl_object_id($this)] = $vars;
    }

    /**
     * Get the modified fields.
     *
     * @return array
     */
    public function getModifiedFields(): array
    {
        $original = $this->originals();
        $modified = [];

        foreach ($this->vars() as $key => $value) {
            if (!array_key_exists($key, $original) || $original[$key] !== $value) {
                $modified[] = $key;
            }
        }

        return $modified;
    }

    public static function getRelationNames(): array
    {
        $relations = [];

        foreach (static::getConstructorParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof \ReflectionUnionType) {
                foreach ($type->getTypes() as $unionType) {
                    $type = $unionType;
                    break;
                }
            }

            if (! $type->isBuiltin()) {
                $class = $type->getName();
                if (is_subclass_of($class, Dto::class)) {
                    $relations[] = $parameter->getName();
                }
            }
        }

        foreach (static::$extends as $key => $types) {
            $types = is_array($types) ? $types : explode('|', $types);

            foreach ($types as $type) {
                if ($type && class_exists($type)) {
                    if (is_subclass_of($type, Dto::class)) {
                        $relations[] = $key;
                    }
                }
            }
        }

        return $relations;
    }

    public static function getPropertyNames(): array
    {
        $properties = [];

        foreach (static::getConstructorParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof \ReflectionUnionType) {
                foreach ($type->getTypes() as $unionType) {
                    $type = $unionType;
                    break;
                }
            }
            if ($type->isBuiltin()) {
                $properties[] = $parameter->getName();
            } else {
                $class = $type->getName();
                if (is_subclass_of($class, Dto::class)) {
                    $properties[] = $parameter->getName();
                } elseif (is_subclass_of($class, Collection::class) || $class === Collection::class) {
                    $properties[] = $parameter->getName();
                } elseif (is_subclass_of($class, Model::class)) {
                    $properties[] = $parameter->getName();
                } elseif (is_subclass_of($class, Carbon::class) || $class === Carbon::class) {
                    $properties[] = $parameter->getName();
                }
            }
        }

        foreach (static::$extends as $key => $types) {
            $types = is_array($types) ? $types : explode('|', $types);
            $type = $types[0];
            $isBuiltin = in_array($type, ['string', 'int', 'float', 'bool', 'array', 'object', 'null', 'mixed', 'callable', 'iterable', 'false', 'true', 'resource']);

            if ($isBuiltin) {
                $properties[] = $key;
            } else {
                foreach ($types as $type) {
                    if ($type && class_exists($type)) {
                        if (is_subclass_of($type, Dto::class)) {
                            $properties[] = $key;
                            break;
                        } else {
                            if (is_subclass_of($type, Collection::class) || $type === Collection::class) {
                                $properties[] = $key;
                                break;
                            } else {
                                if (is_subclass_of($type, Model::class)) {
                                    $properties[] = $key;
                                    break;
                                } else {
                                    if (is_subclass_of($type, Carbon::class) || $type === Carbon::class) {
                                        $properties[] = $key;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $properties;
    }

    public static function getNames(): array
    {
        $parameters = [];

        foreach (static::getConstructorParameters() as $parameter) {
            $parameters[] = $parameter->getName();
        }

        foreach (static::$extends as $key => $types) {
            $parameters[] = $key;
        }

        return $parameters;
    }

    /**
     * Get the reflection class
     *
     * @return ReflectionClass
     */
    protected static function reflection(): ReflectionClass
    {
        if (isset(static::$__reflections[static::class])) {
            return static::$__reflections[static::class];
        }
        return static::$__reflections[static::class] = new ReflectionClass(static::class);
    }

    public static function getReflection(): ReflectionDto
    {
        return ReflectionDto::fromArray([
            'name' => static::class,
            'names' => static::getNames(),
            'properties' => static::getConstructorParameters(),
            'relationNames' => static::getRelationNames(),
            'propertyNames' => static::getPropertyNames(),
        ]);
    }
}

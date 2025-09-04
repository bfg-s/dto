<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Attributes\DtoExceptProperty;
use Bfg\Dto\Attributes\DtoFromCache;
use Bfg\Dto\Attributes\DtoFromConfig;
use Bfg\Dto\Attributes\DtoFromRequest;
use Bfg\Dto\Attributes\DtoFromRoute;
use Bfg\Dto\Attributes\DtoMapApi;
use Bfg\Dto\Attributes\DtoMapFrom;
use Bfg\Dto\Attributes\DtoMapTo;
use Bfg\Dto\Attributes\DtoMutateTo;
use Bfg\Dto\Attributes\DtoToResource;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait DtoToArrayTrait
{
    /**
     * @param  string  ...$keys
     * @return array
     */
    public function toArrayOnly(string ...$keys): array
    {
        return array_filter($this->toArray(), fn ($key) => in_array($key, $keys), ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param  string  ...$keys
     * @return array
     */
    public function toArrayExclude(string ...$keys): array
    {
        return array_filter($this->toArray(), fn ($key) => ! in_array($key, $keys), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $parameters = static::getConstructorParameters();
        $result = [];
        $originals = $this->originals();
        $keysOnly = static::$__requestKeys[static::class][spl_object_id($this)] ?? [];
        $paramNames = [];

        foreach ($parameters as $parameter) {
            $key = $parameter->getName();
            $originalKey = $parameter->getName();
            if (in_array($key, static::$dtoHidden)) {
                continue;
            }
            if ($keysOnly && ! in_array($key, $keysOnly)) {
                continue;
            }
            $paramNames[] = $key;
            $value = $this->{$key} ?? null;
            $resource = null;
            $foreign = false;
            $attributes = $parameter->getAttributes(DtoExceptProperty::class);
            if (count($attributes) > 0) {
                continue;
            }
            $attributes = $parameter->getAttributes(DtoToResource::class);
            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                if (is_subclass_of($instance->class, JsonResource::class)) {
                    $resource = $instance->class;
                } else {
                    throw new \InvalidArgumentException('The class for DtoToResource must be a subclass of JsonResource.');
                }
            }
            $attributes = $parameter->getAttributes(DtoMapTo::class);
            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                if (! empty($instance->name)) {
                    $key = $instance->name;
                } else {
                    throw new \InvalidArgumentException('The name for DtoMapTo must be a non-empty string.');
                }
                break;
            }
            if ($key === $originalKey) {
                $attributes = $parameter->getAttributes(DtoMapApi::class);
                foreach ($attributes as $attribute) {
                    $key = Str::snake($key);
                    break;
                }
            }
            $attributes = $parameter->getAttributes(DtoMutateTo::class);

            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                $instance->cb = is_string($instance->cb) && ! is_callable($instance->cb)
                    ? [$this, $instance->cb] : $instance->cb;
                if (is_callable($instance->cb)) {
                    $value = call_user_func($instance->cb, $value);
                    break;
                } else {
                    throw new \InvalidArgumentException('The callback for DtoMutateTo must be callable.');
                }
            }
            $attributes = $parameter->getAttributes(DtoFromRoute::class);
            if ($attributes) {
                $foreign = true;
            }
            $attributes = $parameter->getAttributes(DtoFromConfig::class);
            if ($attributes) {
                $foreign = true;
            }
            $attributes = $parameter->getAttributes(DtoFromRequest::class);
            if ($attributes) {
                $foreign = true;
            }
            $attributes = $parameter->getAttributes(DtoFromCache::class);
            if ($attributes) {
                $foreign = true;
            }

            if ($foreign) {
                if (! dto_data_exists($originals, $key)) {
                    continue;
                }
            }

            if (is_array($value) || $value instanceof Collection) {
                if ($resource) {
                    $value = $resource::collection($value)->toArray(request());
                } else {
                    if ($value instanceof Collection) {
                        $value = $value->toArray();
                    }

                    foreach ($value as $k => $v) {
                        if ($v instanceof Arrayable) {
                            $value[$k] = $v->toArray();
                        }
                    }
                }
            } else {
                if ($resource) {
                    $value = (new $resource($value))->toArray(request());
                }
                $value = $this->buildObjectValue($value, $key);

                if (is_object($value) && ! enum_exists(get_class($value))) {
                    continue;
                }
            }

            $result[$key] = $value;
        }

        $property = (new \ReflectionProperty(static::class, 'extends'));

        foreach (static::$extends as $key => $types) {
            $originalKey = $key;
            if (in_array($key, static::$dtoHidden)) {
                continue;
            }
            if ($keysOnly && ! in_array($key, $keysOnly)) {
                continue;
            }
            $paramNames[] = $key;
            $foreign = false;
            $resource = null;
            $value = static::$__parameters[static::class][spl_object_id($this)][$key] ?? null;

            if (is_array($value) || $value instanceof Collection) {
                if ($value instanceof Collection) {
                    $value = $value->toArray();
                }

                foreach ($value as $k => $v) {
                    if ($v instanceof Arrayable) {
                        $value[$k] = $v->toArray();
                    }
                }
            } else {
                $value = $this->buildObjectValue($value, $key);
                if (is_object($value) && ! enum_exists(get_class($value))) {
                    continue;
                }
            }
            $attributes = $property->getAttributes(DtoExceptProperty::class);
            if (count($attributes) > 0) {
                $instance = $attributes[0]->newInstance();
                if ($instance->from === $key) {
                    continue;
                }
            }
            $attributes = $property->getAttributes(DtoToResource::class);
            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                if ($instance->from === $key) {
                    if (is_subclass_of($instance->class, JsonResource::class)) {
                        $resource = $instance->class;
                    } else {
                        throw new \InvalidArgumentException('The class for DtoToResource must be a subclass of JsonResource.');
                    }
                }
            }
            $attributes = $property->getAttributes(DtoMapTo::class);
            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                if ($instance->from === $key) {
                    $key = $instance->name;
                    break;
                }
            }
            if ($key === $originalKey) {
                $attributes = $property->getAttributes(DtoMapApi::class);
                foreach ($attributes as $attribute) {
                    $instance = $attribute->newInstance();
                    if ($instance instanceof DtoMapApi) {
                        if (! $instance->from || $instance->from === $key) {
                            $key = Str::snake($key);
                            break;
                        }
                    }
                }
            }
            $attributes = $property->getAttributes(DtoMutateTo::class);
            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                if ($instance->from === $key) {
                    $instance->cb = is_string($instance->cb) && ! is_callable($instance->cb)
                        ? [$this, $instance->cb] : $instance->cb;
                    if (is_callable($instance->cb)) {
                        $value = call_user_func($instance->cb, $value);
                        break;
                    } else {
                        throw new \InvalidArgumentException('The callback for DtoMutateTo must be callable.');
                    }
                }
            }
            $attributes = $property->getAttributes(DtoFromRoute::class);
            if ($attributes) {
                foreach ($attributes as $attribute) {
                    $instance = $attribute->newInstance();
                    if ($instance->from === $key) {
                        $foreign = true;
                        break;
                    }
                }
            }
            $attributes = $property->getAttributes(DtoFromConfig::class);
            if ($attributes) {
                foreach ($attributes as $attribute) {
                    $instance = $attribute->newInstance();
                    if ($instance->from === $key) {
                        $foreign = true;
                        break;
                    }
                }
            }
            $attributes = $property->getAttributes(DtoFromRequest::class);
            if ($attributes) {
                foreach ($attributes as $attribute) {
                    $instance = $attribute->newInstance();
                    if ($instance->from === $key) {
                        $foreign = true;
                        break;
                    }
                }
            }
            $attributes = $property->getAttributes(DtoFromCache::class);
            if ($attributes) {
                foreach ($attributes as $attribute) {
                    $instance = $attribute->newInstance();
                    if ($instance->from === $key) {
                        $foreign = true;
                        break;
                    }
                }
            }

            if ($foreign) {
                if (data_get($originals, $key, '__NOT_FOUND') === '__NOT_FOUND') {
                    continue;
                }
            }

            if ($resource) {
                $value = (new $resource($value))->toArray(request());
            }

            $result[$key] = $value;
        }

        // Dynamically add extends properties
        foreach (get_object_vars($this) as $key => $value) {
            if (! isset($result[$key]) && ! in_array($key, $paramNames)) {
                if (in_array($key, static::$dtoHidden)) {
                    continue;
                }
                if ($keysOnly && ! in_array($key, $keysOnly)) {
                    continue;
                }

                if ($value instanceof Arrayable) {
                    $result[$key] = $value->toArray();
                } elseif ($value instanceof Model) {
                    $result[$key] = $value->id;
                } elseif ($value instanceof Carbon) {
                    $result[$key] = $value->format(static::$dtoDateFormat);
                } else {
                    $result[$key] = $value;
                }
            }
        }

        foreach (static::$dtoEncrypted as $key) {

            if (array_key_exists($key, $result)) {
                $result[$key]
                    = static::currentEncrypter()->encrypt($result[$key]);
            }
        }

        foreach ($result as $key => $value) {
            if (static::isEnumCastable($key)) {
                $result[$key] = static::setEnumCastableAttribute($key, $value);
            } elseif (static::isClassCastable($key)) {
                $result[$key] = static::setClassCastableAttribute($key, $value, $result);
            }
        }

        $methods = get_class_methods($this);

        foreach ($methods as $method) {
            if ($method !== 'with' && str_starts_with($method, 'with')) {
                $name = Str::of($method)->replaceFirst('with', '')->snake()->camel()->toString();
                if (method_exists($this, $method)) {
                    $result[$name] = $this->{$method}();
                }
            }
        }

        $isSnakeKeys = $this->getSetting('snakeKeys', false);
        $isCamelKeys = $this->getSetting('camelKeys', false);

        if ($isSnakeKeys) {
            $result = $this->recursiveChangeKeyCaseFromArray($result, 'snake');
        }

        if ($isCamelKeys) {
            $result = $this->recursiveChangeKeyCaseFromArray($result, 'camel');
        }

        return $result;
    }

    /**
     * @param  array  $array
     * @param  string  $case
     * @return array
     */
    protected function recursiveChangeKeyCaseFromArray(array $array, string $case): array
    {
        return collect($array)->mapWithKeys(fn ($value, $key) => [
            Str::of($key)->{$case}()->toString() => is_array($value) ? $this->recursiveChangeKeyCaseFromArray($value, $case) : $value
        ])->toArray();
    }

    /**
     * @param  mixed  $value
     * @param  string  $key
     * @return mixed
     */
    protected function buildObjectValue(mixed $value, string $key): mixed
    {
        if ($value instanceof Model) {
            $value = $value->id;
        }
        if ($value instanceof Arrayable && ! $value instanceof Request) {
            $value = $value->toArray();
        }
        if ($value instanceof Carbon) {
            $value = $value->format(static::$dtoDateFormat);
        }

        return $value;
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->set($offset);
    }
}

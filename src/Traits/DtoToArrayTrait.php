<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Attributes\DtoFromCache;
use Bfg\Dto\Attributes\DtoFromConfig;
use Bfg\Dto\Attributes\DtoFromRequest;
use Bfg\Dto\Attributes\DtoFromRoute;
use Bfg\Dto\Attributes\DtoName;
use Bfg\Dto\Attributes\DtoToResource;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

trait DtoToArrayTrait
{
    /**
     * @param  string  ...$keys
     * @return array
     */
    public function toArrayOnly(string ...$keys): array
    {
        return array_filter($this->toArray(), fn($key) => in_array($key, $keys), ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param  string  ...$keys
     * @return array
     */
    public function toArrayExclude(string ...$keys): array
    {
        return array_filter($this->toArray(), fn($key) => ! in_array($key, $keys), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        $parameters = static::getConstructorParameters();
        $result = [
            // 'version' => static::$version
        ];
        $originals = $this->originals();

        foreach ($parameters as $parameter) {
            $key = $parameter->getName();
            if (in_array($key, static::$hidden) && ! static::$__strictToArray) {
                continue;
            }
            $value = $this->{$key} ?? null;
            $resource = null;
            $foreign = false;
            $attributes = $parameter->getAttributes(DtoToResource::class);
            if (! static::$__strictToArray) {
                foreach ($attributes as $attribute) {
                    $instance = $attribute->newInstance();
                    if (is_subclass_of($instance->class, JsonResource::class)) {
                        $resource = $instance->class;
                    }
                }
            }
            if (! $resource && ! static::$__strictToArray) {
                $attributes = $parameter->getAttributes(DtoName::class);
                foreach ($attributes as $attribute) {
                    $instance = $attribute->newInstance();
                    $key = $instance->name;
                    break;
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
                if (! array_key_exists($key, $originals)) {
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

            if (in_array($key, static::$hidden) && ! static::$__strictToArray) {
                continue;
            }
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

            $attributes = $property->getAttributes(DtoToResource::class);
            if (! static::$__strictToArray) {
                foreach ($attributes as $attribute) {
                    $instance = $attribute->newInstance();
                    if ($instance->from === $key) {
                        if (is_subclass_of($instance->class, JsonResource::class)) {
                            $resource = $instance->class;
                        }
                    }
                }
            }
            if (! $resource && ! static::$__strictToArray) {
                $attributes = $property->getAttributes(DtoName::class);
                foreach ($attributes as $attribute) {
                    $instance = $attribute->newInstance();
                    if ($instance->from === $key) {
                        $key = $instance->name;
                        break;
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
            $attributes = $parameter->getAttributes(DtoFromRequest::class);
            if ($attributes) {
                foreach ($attributes as $attribute) {
                    $instance = $attribute->newInstance();
                    if ($instance->from === $key) {
                        $foreign = true;
                        break;
                    }
                }
            }
            $attributes = $parameter->getAttributes(DtoFromCache::class);
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
                if (! array_key_exists($key, $originals)) {
                    continue;
                }
            }

            if ($resource) {
                $value = (new $resource($value))->toArray(request());
            }

            $result[$key] = $value;
        }

        foreach (static::$encrypted as $key) {

            if (array_key_exists($key, $result)) {
                $result[$key]
                    = static::currentEncrypter()->encrypt($result[$key]);
            }
        }

        foreach ($result as $key => $value) {
            if (static::isEnumCastable($key)) {
                $result[$key] = static::setEnumCastableAttribute($key, $value);
            } else if (static::isClassCastable($key)) {
                $result[$key] = static::setClassCastableAttribute($key, $value, $result);
            }
        }

        if (! static::$__strictToArray) {
            $methods = get_class_methods($this);

            foreach ($methods as $method) {
                if ($method !== 'with' && str_starts_with($method, 'with')) {
                    $name = Str::of($method)->replaceFirst('with', '')->snake()->camel()->toString();
                    if (method_exists($this, $method)) {
                        $result[$name] = $this->{$method}();
                    }
                }
            }
        } else {
            static::$__strictToArray = false;
        }

        return $result;
    }

    /**
     * @param  mixed  $value
     * @param  string  $key
     * @return mixed
     */
    protected function buildObjectValue(mixed $value, string $key): mixed
    {
        $mutatorMethodName = 'toArray'.ucfirst(Str::camel($key));
        if (method_exists($this, $mutatorMethodName)) {
            $value = $this->{$mutatorMethodName}($value);
        }
        if ($value instanceof Model) {
            $value = $value->id;
        }
        if ($value instanceof Arrayable && ! $value instanceof Request) {
            $value = $value->toArray();
        }
        if ($value instanceof Carbon) {
            $value = $value->format(static::$dateFormat);
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

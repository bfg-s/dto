<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Dto;
use Bfg\Dto\Exceptions\DtoPropertyAreImmutableException;
use Bfg\Dto\Exceptions\DtoPropertyDoesNotExistException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait DtoMagicTrait
{
    public function __call(string $method, array $parameters)
    {
        if ($model = $this->model()) {

            return $model->{$method}(...$parameters);
        }

        return null;
    }

    /**
     * Disable the ability to set properties.
     *
     * @param $name
     * @param $value
     * @return void
     * @throws \Bfg\Dto\Exceptions\DtoPropertyAreImmutableException
     */
    public function __set($name, $value)
    {
        if (array_key_exists($name, (array) static::$extends)) {

            $this->set($name, $value);
        }

        if (static::$allowDynamicProperties || ! count(static::getConstructorParameters())) {
            $this->{$name} = $value;
        } else {
            throw new DtoPropertyAreImmutableException();
        }
    }

    /**
     * Trap for computed properties.
     *
     * @param  string  $name
     * @return void
     * @throws \Bfg\Dto\Exceptions\DtoPropertyDoesNotExistException
     */
    public function __get(string $name)
    {
        $start = static::startTime();
        if (array_key_exists($name, static::$extends)) {

            return static::$__parameters[static::class][spl_object_id($this)][$name] ?? null;
        }

        if (method_exists($this, $name)) {
            $this->log("computed" . ucfirst(Str::camel($name)), compact('name'), static::endTime($start));
            return $this->{$name}();
        }

        if (Str::startsWith($name, 'lazy')) {
            $originalName = $name;
            $name = Str::of($name)->replaceFirst('lazy', '')->snake()->camel()->toString();
            if (isset(static::$__lazyCache[static::class][spl_object_id($this)][$name])) {
                return static::$__lazyCache[static::class][spl_object_id($this)][$name];
            }
            if (method_exists($this, $name)) {
                $this->log($originalName, compact('name'), static::endTime($start));
                return static::$__lazyCache[static::class][spl_object_id($this)][$name] = $this->{$name}();
            } elseif ($this->has($name)) {
                $this->log($originalName, compact('name'), static::endTime($start));
                return static::$__lazyCache[static::class][spl_object_id($this)][$name] = $this->get($name);
            }
        }

        if ($this->has($name)) {

            return $this->get($name);
        }

        if ($model = $this->model()) {

            return $model->{$name};
        }

        throw new DtoPropertyDoesNotExistException($name);
    }

    /**
     * Check if the attribute exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    /**
     * Clone the instance.
     *
     * @return void
     */
    public function __clone(): void
    {
        $start = static::startTime();

        $vars = static::fireEvent('clone', $this->vars(), static::SET_CURRENT_DATA, $this);

        foreach ($vars as $key => $get_object_var) {
            if (
                $get_object_var instanceof Dto
            ) {
                $this->{$key} = clone $get_object_var;
            } else {
                $this->{$key} = $get_object_var;
            }
        }

        $this->log('clonedFrom', compact('vars'), static::endTime($start));
    }

    /**
     * Convert the object to its string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Serialize the object.
     *
     * @return array
     */
    public function __serialize(): array
    {
        $start = static::startTime();
        $result = static::fireEvent('serialize', $this->vars(), static::SET_CURRENT_DATA, $this);
        foreach ($result as $key => $item) {
            if ($item instanceof Dto) {
                $result[$key] = $item->toSerialize();
            } elseif ($item instanceof DtoCollection) {
                $result[$key] = $item->toSerialize();
            } elseif ($item instanceof Carbon) {
                $result[$key] = $item->format((string) static::$dtoDateFormat);
            } elseif ($item instanceof Model) {
                $result[$key] = $item->id;
            } elseif (is_object($item)) {
                $result[$key] = serialize($item);
            } else {
                $result[$key] = $item;
            }
        }

        $result['__meta'] = static::$__meta[static::class][spl_object_id($this)] ?? [];
        $result['__model'] = static::$__models[static::class][spl_object_id($this)] ?? null;
        if (($model = $result['__model']) && $model instanceof Model) {
            $result['__model'] = [
                'class' => get_class($model),
                'key_name' => $model->getKeyName(),
                'key' => $model->getKey(),
            ];
        }
        $this->log('serialized', ms: static::endTime($start));
        $result['__logs'] = static::$__logs[static::class][spl_object_id($this)] ?? [];

        return $result;
    }

    /**
     * Unserialize the object.
     *
     * @param  array  $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $start = static::startTime();
        $this->setMeta($data['__meta'] ?? []);
        static::$__logs[static::class][spl_object_id($this)] = $data['__logs'] ?? [];
        if ($model = $data['__model']) {
            if (is_array($model) && isset($model['class'], $model['key_name'], $model['key']) && class_exists($model['class'])) {
                $class = $model['class'];
                $this->setModel((new $class())->where($model['key_name'], $model['key'])->first());
            }
        }
        unset($data['__meta'], $data['__logs'], $data['__model']);
        $data = static::fireEvent('unserialize', $data, static::SET_CURRENT_DATA, $this);
        static::$__logMute = true;
        $this->fill($data);
        static::$__logMute = false;
        $this->log('unserialized', $data, static::endTime($start));
    }

    /**
     * Debug info.
     *
     * @return array|null
     */
    public function __debugInfo(): ?array
    {
        $add = [];

        if (static::$dtoLogsEnabled) {

            $add['logs'] = $this->logs();
        }

        if ($model = $this->model()) {

            $add['model'] = $model;
        }

        return array_merge(
            static::$__parameters[static::class][spl_object_id($this)] ?? [],
            ['meta' => $this->getMeta()],
            $add
        );
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $start = static::startTime();

        static::fireEvent('destruct', null, $this);

        $objectId = spl_object_id($this);

        unset(
            static::$__logStartTime[static::class][$objectId],
            static::$__requestKeys[static::class][$objectId],
            static::$__parameters[static::class][$objectId],
            static::$__originals[static::class][$objectId],
            static::$__lazyCache[static::class][$objectId],
            static::$__settings[static::class][$objectId],
            static::$__models[static::class][$objectId],
            static::$__logs[static::class][$objectId],
            static::$__meta[static::class][$objectId],
            static::$__vars[static::class][$objectId],
        );

        $this->log('destruct', ms: static::endTime($start));
    }
}

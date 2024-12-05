<?php

namespace Bfg\Dto\Traits;

use Bfg\Dto\Dto;
use Bfg\Dto\Exceptions\DtoPropertyAreImmutableException;
use Bfg\Dto\Exceptions\DtoPropertyDoesNotExistException;
use Illuminate\Support\Str;

trait DtoMagicTrait
{
    /**
     * Disable the ability to set properties.
     *
     * @param $name
     * @param $value
     * @return mixed
     * @throws \Bfg\Dto\Exceptions\DtoPropertyAreImmutableException
     */
    public function __set($name, $value)
    {
        throw new DtoPropertyAreImmutableException();
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
        if (method_exists($this, $name)) {
            $this->log("computed" . ucfirst(Str::camel($name)), compact('name'));
            return $this->{$name}();
        }

        // check is name starts with "lazy"
        if (Str::startsWith($name, 'lazy')) {
            $originalName = $name;
            $name = Str::of($name)->replaceFirst('lazy', '')->snake()->camel()->toString();
            if (isset(static::$__lazyCache[static::class][spl_object_id($this)][$name])) {
                return static::$__lazyCache[static::class][spl_object_id($this)][$name];
            }
            if (method_exists($this, $name)) {
                $this->log($originalName, compact('name'));
                return static::$__lazyCache[static::class][spl_object_id($this)][$name] = $this->{$name}();
            } else if (property_exists($this, $name)) {
                $this->log($originalName, compact('name'));
                return static::$__lazyCache[static::class][spl_object_id($this)][$name] = $this->get($name);
            }
        }

        if (property_exists($this, $name)) {

            return $this->get($name);
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

        $this->log('clonedFrom', compact('vars'));
    }

    /**
     * Convert the object to its string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        $this->log('ConvertedToString');
        return $this->toJson();
    }

    /**
     * Serialize the object.
     *
     * @return array
     */
    public function __serialize(): array
    {
        $this->log('serialized');
        $result = static::fireEvent('serialize', $this->toArray(), static::SET_CURRENT_DATA, $this);
        $result['__meta'] = $this->getMeta();
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
        $this->setMeta($data['__meta'] ?? []);
        unset($data['__meta']);
        $data = static::fireEvent('unserialize', $data, static::SET_CURRENT_DATA, $this);
        $this->fill($data);
        $this->log('unserialized', $data);
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $this->log('destruct');

        static::fireEvent('destruct', null, $this);

        unset(
            static::$__originals[static::class][spl_object_id($this)],
            static::$__lazyCache[static::class][spl_object_id($this)],
            static::$__logs[static::class][spl_object_id($this)],
            static::$__meta[static::class][spl_object_id($this)],
            static::$__vars[static::class][spl_object_id($this)],
        );
    }
}

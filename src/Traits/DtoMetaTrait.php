<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

trait DtoMetaTrait
{


    /**
     * Set meta data
     *
     * @param  array<string, mixed>|string  $meta
     * @param  mixed|null  $value
     * @return $this
     */
    public function setMeta(array|string $meta, mixed $value = null): static
    {
        if (is_string($meta)) {
            $meta = [$meta => value($value)];
        }
        static::$__meta[static::class][$this->dtoId()] = array_merge(
            static::$__meta[static::class][$this->dtoId()] ?? [],
            $meta
        );

        return $this;
    }

    /**
     * Unset meta data
     *
     * @param  string  $key
     * @return $this
     */
    public function unsetMeta(string $key): static
    {
        unset(static::$__meta[static::class][$this->dtoId()][$key]);
        return $this;
    }

    /**
     * Clean all meta data
     *
     * @return $this
     */
    public function cleanMeta(): static
    {
        unset(static::$__meta[static::class][$this->dtoId()]);
        return $this;
    }

    /**
     * Get meta data
     *
     * @param  string|null  $key
     * @return array|mixed|null
     */
    public function getMeta(string $key = null, mixed $default = null): mixed
    {
        return $key
            ? (static::$__meta[static::class][$this->dtoId()][$key] ?? value($default, $key))
            : (static::$__meta[static::class][$this->dtoId()] ?? value($default, $key));
    }

    /**
     * Has meta data
     *
     * @param  string  $key
     * @return bool
     */
    public function hasMeta(string $key): bool
    {
        return isset(static::$__meta[static::class][$this->dtoId()][$key]);
    }
}

<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

trait DtoMetaTrait
{
    /**
     * Set meta data
     *
     * @param  array  $meta
     * @return $this
     */
    public function setMeta(array $meta): static
    {
        static::$__meta[static::class][spl_object_id($this)] = array_merge(
            static::$__meta[static::class][spl_object_id($this)] ?? [],
            $meta
        );

        $this->log('setMeta', $meta);

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
        unset(static::$__meta[static::class][spl_object_id($this)][$key]);
        $this->log('unsetMeta', compact('key'));
        return $this;
    }

    /**
     * Get meta data
     *
     * @param  string|null  $key
     * @return array|mixed|null
     */
    public function getMeta(string $key = null): mixed
    {
        return $key
            ? (static::$__meta[static::class][spl_object_id($this)][$key] ?? null)
            : (static::$__meta[static::class][spl_object_id($this)] ?? []);
    }
}

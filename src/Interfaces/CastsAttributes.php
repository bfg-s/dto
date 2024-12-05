<?php

namespace Bfg\Dto\Interfaces;

/**
 * @template TGet
 * @template TSet
 */
interface CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  array<string, mixed>  $attributes
     * @return TGet|null
     */
    public function get(string $key, mixed $value, array $attributes): mixed;

    /**
     * Transform the attribute to its underlying model values.
     *
     * @param  string  $key
     * @param  TSet|null  $value
     * @param  array<string, mixed>  $attributes
     * @return mixed
     */
    public function set(string $key, mixed $value, array $attributes): mixed;
}

<?php

namespace Bfg\Dto\Interfaces;

interface CastsInboundAttributes
{
    /**
     * Transform the attribute to its underlying model values.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  array<string, mixed>  $attributes
     * @return mixed
     */
    public function set(string $key, mixed $value, array $attributes): mixed;
}

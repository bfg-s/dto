<?php

namespace Bfg\Dto\Collections;

use Bfg\Dto\Dto;
use Bfg\Dto\Traits\DtoCollectionMethodsTrait;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Support\Collection;
use Illuminate\Support\Collection as BaseCollection;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 * @implements \ArrayAccess<TKey, TValue>
 * @implements \Illuminate\Support\Enumerable<TKey, TValue>
 * @extends BaseCollection<TKey, TValue>
 */
class DtoCollection extends BaseCollection implements Castable
{
    use DtoCollectionMethodsTrait;

    /**
     * Create a new collection.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue>|null  $items
     * @return void
     */
    public function __construct($items = [])
    {
        parent::__construct($items);
    }
}

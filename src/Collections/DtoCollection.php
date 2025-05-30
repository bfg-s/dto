<?php

namespace Bfg\Dto\Collections;

use Bfg\Dto\Traits\DtoCollectionMethodsTrait;
use Illuminate\Support\Collection as BaseCollection;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 * @implements \ArrayAccess<TKey, TValue>
 * @implements \Illuminate\Support\Enumerable<TKey, TValue>
 */
class DtoCollection extends BaseCollection
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

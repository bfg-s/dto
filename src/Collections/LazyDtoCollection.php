<?php

namespace Bfg\Dto\Collections;

use Bfg\Dto\Traits\DtoCollectionMethodsTrait;
use Illuminate\Support\LazyCollection;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 * @implements \Illuminate\Support\Enumerable<TKey, TValue>
 */
class LazyDtoCollection extends LazyCollection
{
    use DtoCollectionMethodsTrait;
}

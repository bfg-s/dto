<?php

namespace Bfg\Dto\Collections;

use Bfg\Dto\Traits\DtoCollectionMethodsTrait;
use Illuminate\Support\LazyCollection as BaseLazyCollection;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 * @implements \Illuminate\Support\Enumerable<TKey, TValue>
 * @extends BaseLazyCollection<TKey, TValue>
 */
class LazyDtoCollection extends BaseLazyCollection
{
    use DtoCollectionMethodsTrait;
}

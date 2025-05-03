<?php

namespace Bfg\Dto\Collections;

use Bfg\Dto\Traits\DtoCollectionMethodsTrait;
use Illuminate\Support\Collection;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 * @implements \ArrayAccess<TKey, TValue>
 * @implements \Illuminate\Support\Enumerable<TKey, TValue>
 */
class DtoCollection extends Collection
{
    use DtoCollectionMethodsTrait;
}

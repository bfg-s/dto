<?php

namespace Bfg\Dto\Collections;

use Bfg\Dto\Dto;
use Bfg\Dto\Traits\DtoCollectionMethodsTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class LazyDtoCollection extends LazyCollection
{
    use DtoCollectionMethodsTrait;
}

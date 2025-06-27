<?php

namespace Bfg\Dto\Tests;

use Bfg\Dto\Dto;

class Test3Dto extends Dto
{
    public function __construct(
        public int|string $name,
    ) {
    }
}

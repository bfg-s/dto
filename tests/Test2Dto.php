<?php

namespace Bfg\Dto\Tests;

use Bfg\Dto\Dto;

class Test2Dto extends Dto
{
    public function __construct(
        public string $name,
        public string|null $email,
    ) {
    }
}

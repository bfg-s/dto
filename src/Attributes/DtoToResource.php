<?php

namespace Bfg\Dto\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class DtoToResource
{
    public function __construct(
        public string $class
    ) {
    }
}

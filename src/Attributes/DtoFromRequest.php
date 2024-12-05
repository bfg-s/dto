<?php

namespace Bfg\Dto\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class DtoFromRequest
{
    public function __construct(
        public ?string $name = null
    ) {
    }
}

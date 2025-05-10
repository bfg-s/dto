<?php

namespace Bfg\Dto\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class DtoFromRoute
{
    public function __construct(
        public ?string $name = null,
        public ?string $from = null,
    ) {
    }
}

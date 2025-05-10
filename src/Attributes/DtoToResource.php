<?php

namespace Bfg\Dto\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class DtoToResource
{
    public function __construct(
        public string $class,
        public ?string $from = null,
    ) {
    }
}

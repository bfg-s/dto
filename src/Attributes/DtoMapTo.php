<?php

namespace Bfg\Dto\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class DtoMapTo
{
    public function __construct(
        public string $name,
        public ?string $from = null,
    ) {
    }
}

<?php

namespace Bfg\Dto\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class DtoMapApi
{
    public function __construct(
        public ?string $from = null,
    ) {
    }
}

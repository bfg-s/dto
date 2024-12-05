<?php

namespace Bfg\Dto\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER|Attribute::IS_REPEATABLE)]
class DtoName
{
    public function __construct(
        public string $name
    ) {
    }
}

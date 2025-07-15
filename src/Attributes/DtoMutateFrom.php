<?php

namespace Bfg\Dto\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class DtoMutateFrom
{
    public function __construct(
        public array|string $cb,
        public ?string $from = null,
    ) {
    }
}

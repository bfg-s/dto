<?php

namespace Bfg\Dto\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class DtoMutateTo
{
    /**
     * @param  array|non-empty-string  $cb
     * @param  string|null  $from
     */
    public function __construct(
        public array|string $cb,
        public ?string $from = null,
    ) {
    }
}

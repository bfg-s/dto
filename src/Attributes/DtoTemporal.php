<?php

namespace Bfg\Dto\Attributes;

use Attribute;

/**
 * DtoTemporal
 *
 * This attribute is used to specify the parameters or properties that should be treated as temporal data types.
 *
 * @package Bfg\Dto\Attributes
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class DtoTemporal
{
    /**
     * Construct a new DtoTemporal instance.
     *
     * @param  non-empty-string|null  $from
     */
    public function __construct(
        public ?string $from = null,
    ) {
    }
}

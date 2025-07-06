<?php

namespace Bfg\Dto\Attributes;

use Attribute;

/**
 * DtoExceptProperty attribute class
 *
 * This attribute is used to specify that a parameter or property should be excluded from the DTO mapping.
 *
 * @package Bfg\Dto\Attributes
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class DtoExceptProperty
{
    /**
     * Construct a new DtoExceptProperty instance.
     *
     * @param  non-empty-string|null  $from
     */
    public function __construct(
        public ?string $from = null,
    ) {
    }
}

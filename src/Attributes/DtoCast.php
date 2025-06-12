<?php

namespace Bfg\Dto\Attributes;

use Attribute;

/**
 * DtoCast attribute class
 *
 * This attribute is used to specify that a parameter or property should be cast to a specific DTO class.
 *
 * @package Bfg\Dto\Attributes
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class DtoCast
{
    /**
     * Construct a new DtoCast instance.
     *
     * @param  non-empty-string|class-string  $cast
     */
    public function __construct(
        public string $cast,
    ) {
    }
}

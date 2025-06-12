<?php

namespace Bfg\Dto\Attributes;

use Attribute;

/**
 * DtoAuthenticatedUser attribute class
 *
 * This attribute is used to specify that a parameter or property should be populated with the authenticated user
 *
 * @package Bfg\Dto\Attributes
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class DtoAuthenticatedUser
{
    /**
     * Construct a new DtoAuthenticatedUser instance.
     *
     * @param  non-empty-string  $guard
     */
    public function __construct(
        public string $guard = 'web',
    ) {
    }
}

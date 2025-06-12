<?php

namespace Bfg\Dto\Attributes;

use Attribute;

/**
 * DtoClass attribute class
 *
 * This attribute is used to specify that a class is a Data Transfer Object (DTO).
 *
 * @package Bfg\Dto\Attributes
 */
#[Attribute(Attribute::TARGET_CLASS)]
class DtoClass
{
    /**
     * Construct a new DtoCast instance.
     *
     * @param  class-string<\Bfg\Dto\Dto>  $class
     */
    public function __construct(
        public string $class,
    ) {
    }
}

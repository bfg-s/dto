<?php

namespace Bfg\Dto\Attributes;

use Attribute;

/**
 * DtoItems attribute class
 *
 * This attribute is used to specify the class name of a DTO item in a collection.
 *
 * @package Bfg\Dto\Attributes
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class DtoItem
{
    /**
     * Construct a new DtoItems instance.
     *
     * @param  class-string<\Bfg\Dto\Dto>  $className
     */
    public function __construct(
        public string $className,
    ) {
    }
}

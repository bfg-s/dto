<?php

namespace Bfg\Dto\Traits;

trait DtoToStringTrait
{
    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->__toString();
    }
}

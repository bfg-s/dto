<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

trait DtoToSerializeTrait
{
    /**
     * Serialize the instance to a string.
     *
     * @return string
     */
    public function toSerialize(): string
    {
        return serialize($this);
    }
}

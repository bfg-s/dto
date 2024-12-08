<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

trait DtoToBase64Trait
{
    /**
     * Convert the object to base64.
     *
     * @return string
     */
    public function toBase64(): string
    {
        return base64_encode($this->toJson());
    }
}

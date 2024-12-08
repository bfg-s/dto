<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Collections\DtoCollection;

trait DtoToCollectionTrait
{
    /**
     * Generate collection from DTO
     *
     * @return \Bfg\Dto\Collections\DtoCollection
     */
    public function toCollection(): DtoCollection
    {
        return new DtoCollection($this->toArray());
    }
}

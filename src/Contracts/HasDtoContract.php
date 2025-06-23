<?php

declare(strict_types=1);

namespace Bfg\Dto\Contracts;

use Bfg\Dto\Dto;
use Bfg\Dto\Collections\DtoCollection;

/**
 * Interface HasDtoContract
 *
 * This interface is used to mark classes that are DTOs (Data Transfer Objects).
 *
 * @package Bfg\Dto\Contracts
 * @template TDto of Dto<mixed>
 */
interface HasDtoContract
{
    /**
     * Get the DTO instance from the class.
     *
     * @return TDto|DtoCollection<int, TDto>
     */
    public function getDto(): Dto|DtoCollection;
}

<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Illuminate\Support\Fluent;

trait DtoToFluentTrait
{
    /**
     * Convert an object to the fluent object.
     *
     * @return Fluent
     */
    public function toFluent(): Fluent
    {
        return new Fluent($this->toArray());
    }
}

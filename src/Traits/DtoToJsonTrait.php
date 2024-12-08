<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

trait DtoToJsonTrait
{
    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}

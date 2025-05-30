<?php

declare(strict_types=1);

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
        $start = static::startTime();
        $result = $this->toJson();
        $this->log('ConvertedToString', ms: static::endTime($start));
        return $result;
    }
}

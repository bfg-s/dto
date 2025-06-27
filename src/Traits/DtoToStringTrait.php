<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

trait DtoToStringTrait
{
    /**
     * Convert the instance to a string.
     *
     * @param  string|null  $separator
     * @return string
     */
    public function toString(string|null $separator = null): string
    {
        $start = static::startTime();
        $result = $this->toArray();
        $result = implode($separator ?: '|', array_values($result));
        $this->log('ConvertedToString', ms: static::endTime($start));
        return $result;
    }

    /**
     * Convert the instance to a numeric value.
     *
     * @return int|float|string
     */
    public function toNumeric(): int|float|string
    {
        $start = static::startTime();
        $result = $this->first();
        $this->log('ConvertedToNumeric', ms: static::endTime($start));
        return $result;
    }
}

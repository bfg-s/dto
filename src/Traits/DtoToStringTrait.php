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
        return $this->toJson(JSON_FORCE_OBJECT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
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

    public function toLineValues(string $separator = '|'): string
    {
        $start = static::startTime();
        $result = $this->toArray();
        $result = implode($separator, array_values($result));
        $this->log('ConvertedToLineValues', ms: static::endTime($start));
        return $result;
    }

    public function toKeyValueLines(
        string $lineSeparator = ':',
        string $nextLineSeparator = PHP_EOL,
    ): string {
        $start = static::startTime();
        $lines = [];
        foreach ($this->toArray() as $key => $value) {
            $lines[] = $key . $lineSeparator . $value;
        }
        $result = implode($nextLineSeparator, $lines);
        $this->log('ConvertedToKeyValueLines', ms: static::endTime($start));
        return $result;
    }
}

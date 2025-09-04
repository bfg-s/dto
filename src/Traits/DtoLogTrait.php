<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Default\LogsDto;
use Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException;
use Carbon\Carbon;

trait DtoLogTrait
{
    protected static function startTime(): Carbon
    {
        return now();
    }

    protected static function endTime(Carbon $start): float|int
    {
        return $start->diffInMilliseconds(now());
    }

    /**
     * The current data
     *
     * @param  string  $message
     * @param  array  $context
     * @param  int|float  $ms
     * @return \Bfg\Dto\Dto
     */
    public function log(string $message, array $context = [], int|float $ms = 0): static
    {
        if (static::$dtoLogsEnabled && ! static::$__logMute) {

            static::$__logs[static::class][spl_object_id($this)][] = [
                'message' => $message,
                'context' => $context,
                'timestamp' => now()->toDateTimeString(),
                'ms' => $ms,
            ];
        }

        return $this;
    }

    /**
     * Log changes of DTO object
     *
     * @return \Bfg\Dto\Default\LogsDto|null
     */
    public function logs(): ?LogsDto
    {
        $changes = [];
        $original = $this->originals();

        foreach ($this->vars() as $key => $value) {
            if (isset($original[$key]) && $original[$key] !== $value) {
                $changes[$key] = ['old' => $original[$key], 'new' => $value];
            }
        }

        try {
            return LogsDto::fromArray([
                'diffs' => $changes,
                'logs' => static::$__logs[static::class][spl_object_id($this)] ?? null,
                'original' => $original,
            ]);
        } catch (DtoUndefinedArrayKeyException) {
            //
        }
        return null;
    }
}

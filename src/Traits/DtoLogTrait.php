<?php

namespace Bfg\Dto\Traits;

use Bfg\Dto\Default\LogsDto;
use Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException;

trait DtoLogTrait
{
    /**
     * The current data
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function log(string $message, array $context = []): void
    {
        if (static::$logsEnabled) {

            static::$__logs[static::class][spl_object_id($this)][] = [
                'message' => $message,
                'context' => $context,
                'timestamp' => now(),
            ];
        }
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
<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Illuminate\Support\Str;

trait DtoToImportTrait
{
    /**
     * Convert an object to the format string from which the object was created.
     * Used for storing in a database.
     *
     * @return int|float|string|null
     */
    public function toImport(): int|float|string|null
    {
        $importType = static::getImportType($this);
        $type = data_get($importType, 'type');
        $args = data_get($importType, 'args', []);
        $source = data_get($importType, 'source', static::$__source);
        if ($type === 'url') {
            return $source;
        } else if ($type === 'serializeAny') {
            return serialize($this->toArray());
        }
        $method = (str_starts_with($type, 'to') ? '' : 'to') . Str::studly($type);
        if (method_exists($this, $method)) {
            return $this->{$method}(...$args);
        } else if ($source) {
            return $source;
        } else {
            throw new \RuntimeException(sprintf(
                'Method %s does not exist in class %s for import type %s.',
                $method,
                static::class,
                $type
            ));
        }
    }
}

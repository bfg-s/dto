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
        if (isset($importType['manual']) && $importType['manual']) {
            $method = (str_starts_with($type, 'to') ? '' : 'to') . Str::studly($type);
            if (method_exists($this, $method)) {
                $args = isset($importType['args']) && $importType['args'] ? $importType['args'] : [];
                $result = $this->{$method}(...$args);
                if (! is_string($result)) {
                    throw new \RuntimeException(sprintf(
                        'Method %s in class %s for import type %s must return a string.',
                        $method,
                        static::class,
                        $type
                    ));
                }
                return $result;
            } else {
                throw new \RuntimeException(sprintf(
                    'Method %s does not exist in class %s for import type %s.',
                    $method,
                    static::class,
                    $type
                ));
            }
        }
        if ($type === 'url') {
            return data_get($importType, 'source', static::$source);
        } else if ($type === 'serializeDto') {
            return $this->toSerialize();
        } else if ($type === 'serializeAny') {
            return serialize($this->toArray());
        } elseif ($type === 'string') {
            return (string) $this->toString(...$args);
        } elseif ($type === 'numeric') {
            return $this->toNumeric();
        } else {
            return $this->toJson(JSON_UNESCAPED_UNICODE);
        }
    }
}

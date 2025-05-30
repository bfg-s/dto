<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

trait DtoToImportTrait
{
    /**
     * Convert an object to the format string from which the object was created.
     * Used for storing in a database.
     *
     * @return string|null
     */
    public function toImport(): string|null
    {
        $importType = static::getImportType();
        $type = data_get($importType, 'type');
        if ($type === 'url') {
            return data_get($importType, 'options.url', static::$source);
        } else if ($type === 'serializeDto') {
            return $this->toSerialize();
        } else if ($type === 'serializeAny') {
            return serialize($this->toArray());
        } else {
            return $this->toJson(JSON_UNESCAPED_UNICODE);
        }
    }
}

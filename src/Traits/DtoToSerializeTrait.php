<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

trait DtoToSerializeTrait
{
    /**
     * Serialize the instance to a string.
     *
     * @return string
     */
    public function toSerialize(): string
    {
        return serialize($this);
    }

    public function toCompress()
    {
        return base64_encode(gzcompress($this->toSerialize()));
    }

    public static function uncompress(string $data): static
    {
        return unserialize(gzuncompress(base64_decode($data)));
    }
}

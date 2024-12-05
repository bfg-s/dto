<?php

namespace Bfg\Dto\Default;

use Bfg\Dto\Dto;

class ExplainPropertyDto extends Dto
{
    public function __construct(
        public string $name,
        public ?string $casting,
        public string|array|null $type,
        public mixed $default,
        public bool $nullable,
        public bool $isEncrypted,
        public bool $isHidden,
        public string|array|null $rule,
        public mixed $value,
    ) {
    }
}

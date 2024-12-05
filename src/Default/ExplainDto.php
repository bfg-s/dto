<?php

namespace Bfg\Dto\Default;

use Bfg\Dto\Dto;

class ExplainDto extends Dto
{
    public function __construct(
        public string $name,
        public string $ver,
        public bool $logsIsEnabled,
        public array $meta,
        public ExplainPropertyDto|array $properties,
        public array $computed,
        public array $with,
    ) {
    }
}

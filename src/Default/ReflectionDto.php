<?php

namespace Bfg\Dto\Default;

use Bfg\Dto\Dto;

class ReflectionDto extends Dto
{
    public function __construct(
        public string $name,
        public array $names,
        public array $properties,
        public array $relationNames,
        public array $propertyNames,
    ) {
    }
}

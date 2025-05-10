<?php

namespace Bfg\Dto\Default;

use Bfg\Dto\Dto;

class LogsInnerDto extends Dto
{
    public function __construct(
        public string $message,
        public mixed $context,
        public string $timestamp,
        public int|float $ms = 0,
    ) {
    }
}

<?php

namespace Bfg\Dto\Default;

use Bfg\Dto\Dto;
use Carbon\Carbon;

class LogsInnerDto extends Dto
{
    public function __construct(
        public string $message,
        public mixed $context,
        public Carbon $timestamp,
    ) {
    }
}
<?php

namespace Bfg\Dto\Default;

use Bfg\Dto\Dto;

class LogsDto extends Dto
{
    public function __construct(
        public LogsInnerDto|array|null $logs,
        public array $diffs,
        public array $original,
    ) {
    }
}

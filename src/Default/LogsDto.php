<?php

namespace Bfg\Dto\Default;

use Bfg\Dto\Dto;
use Illuminate\Support\Collection;

class LogsDto extends Dto
{
    public function __construct(
        public LogsInnerDto|Collection|null $logs,
        public array $diffs,
        public array $original,
    ) {
    }
}

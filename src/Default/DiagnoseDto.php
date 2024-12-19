<?php

namespace Bfg\Dto\Default;

use Bfg\Dto\Dto;

class DiagnoseDto extends Dto
{
    public function __construct(
        public int|float $totalMs,
        public int $serializedTimes,
        public int $unserializedTimes,
        public array $metaNewerUsed,
        public array $computedNewerUsed,
        public array $propertiesNewerUsed,
    ) {
    }
}

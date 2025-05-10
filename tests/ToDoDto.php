<?php

namespace Bfg\Dto\Tests;

use Bfg\Dto\Dto;

class ToDoDto extends Dto
{
    public function __construct(
        public int $userId,
        public int $id,
        public string $title,
        public bool $completed,
    ) {
    }
}

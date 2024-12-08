<?php

namespace Bfg\Dto\Exceptions;

use Throwable;

class DtoPropertyAreImmutableException extends \Exception
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct("DTO properties are immutable.", 1115, $previous);
    }
}

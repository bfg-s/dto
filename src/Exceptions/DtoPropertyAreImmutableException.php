<?php

namespace Bfg\Dto\Exceptions;

use Throwable;

class DtoPropertyAreImmutableException extends \Exception
{
    public function __construct(int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("DTO properties are immutable.", $code, $previous);
    }
}

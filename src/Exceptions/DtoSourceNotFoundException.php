<?php

namespace Bfg\Dto\Exceptions;

use RuntimeException;
use Throwable;

class DtoSourceNotFoundException extends RuntimeException
{
    public function __construct(string $source, ?Throwable $previous = null)
    {
        parent::__construct("The source [$source] not found!", 1115, $previous);
    }
}

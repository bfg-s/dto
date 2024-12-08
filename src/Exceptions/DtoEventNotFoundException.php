<?php

namespace Bfg\Dto\Exceptions;

use RuntimeException;
use Throwable;

class DtoEventNotFoundException extends RuntimeException
{
    public function __construct(string $event, ?Throwable $previous = null)
    {
        parent::__construct("Event [{$event}] not found in the list of available events.", 1111, $previous);
    }
}

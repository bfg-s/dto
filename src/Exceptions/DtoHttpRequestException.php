<?php

namespace Bfg\Dto\Exceptions;

use RuntimeException;
use Throwable;

class DtoHttpRequestException extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 1119, $previous);
    }
}

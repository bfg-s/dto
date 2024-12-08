<?php

namespace Bfg\Dto\Exceptions;

use RuntimeException;
use Throwable;

class DtoExtensionTypeNotFoundException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Dto extension type not found.', 1112, $previous);
    }
}

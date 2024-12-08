<?php

namespace Bfg\Dto\Exceptions;

use Throwable;

class DtoUndefinedArrayKeyException extends \Exception
{
    public function __construct(string $property, ?Throwable $previous = null)
    {
        parent::__construct("Undefined array key \"$property\"", 1117, $previous);
    }
}

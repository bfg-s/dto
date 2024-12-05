<?php

namespace Bfg\Dto\Exceptions;

use Throwable;

class DtoUndefinedArrayKeyException extends \Exception
{
    public function __construct(string $property, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("Undefined array key \"$property\"", $code, $previous);
    }
}

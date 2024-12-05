<?php

namespace Bfg\Dto\Exceptions;

use Throwable;

class DtoPropertyDoesNotExistException extends \Exception
{
    public function __construct(string $property, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("Property {$property} does not exist.", $code, $previous);
    }
}

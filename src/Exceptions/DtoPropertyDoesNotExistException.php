<?php

namespace Bfg\Dto\Exceptions;

use Throwable;

class DtoPropertyDoesNotExistException extends \Exception
{
    public function __construct(string $property, ?Throwable $previous = null)
    {
        parent::__construct("Property {$property} does not exist.", 1116, $previous);
    }
}

<?php

namespace Bfg\Dto\Exceptions;

use Throwable;

class DtoUndefinedCacheException extends \Exception
{
    public function __construct(string $class, ?Throwable $previous = null)
    {
        parent::__construct("Undefined cache for [$class]", 1118, $previous);
    }
}

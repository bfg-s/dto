<?php

namespace Bfg\Dto\Exceptions;

use Throwable;

class DtoUndefinedCacheException extends \Exception
{
    public function __construct(string $class, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("Undefined cache for [$class]", $code, $previous);
    }
}

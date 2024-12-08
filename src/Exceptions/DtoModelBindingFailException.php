<?php

namespace Bfg\Dto\Exceptions;

use Exception;
use Throwable;

class DtoModelBindingFailException extends Exception
{
    public function __construct($class, $field, $value, Throwable $errorBag = null)
    {
        parent::__construct("Model [$class] by [$field=$value] not found!", 1114, $errorBag);
    }
}

<?php

namespace Bfg\Dto\Exceptions;

use Exception;
use Throwable;

class DtoModelBindingFailException extends Exception
{
    public function __construct($class, $field, $value, $response = null, Throwable $errorBag = null)
    {
        parent::__construct("Model [$class] by [$field=$value] not found!", $response, $errorBag);
    }
}

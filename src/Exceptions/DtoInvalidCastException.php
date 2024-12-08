<?php

namespace Bfg\Dto\Exceptions;

use RuntimeException;

class DtoInvalidCastException extends RuntimeException
{
    /**
     * The class of the affected dto.
     *
     * @var string
     */
    public string $class;

    /**
     * The name of the column.
     *
     * @var string
     */
    public string $column;

    /**
     * The name of the cast type.
     *
     * @var string
     */
    public string $castType;

    /**
     * Create a new exception instance.
     *
     * @param  string  $class
     * @param  string  $column
     * @param  string  $castType
     */
    public function __construct(string $class, $column, $castType)
    {
        parent::__construct("Call to undefined cast [{$castType}] on property [{$column}] in dto [{$class}].", 1113);

        $this->class = $class;
        $this->column = $column;
        $this->castType = $castType;
    }
}

<?php

declare(strict_types=1);

namespace Bfg\Dto\Traps;

/**
 * @template TDto of \Bfg\Dto\Dto
 * @mixin TDto
 */
class DtoCastingStoreTrap
{
    /**
     * Create a new instance of the DtoCastingStoreTrap.
     *
     * @param  class-string<TDto>  $usingDto
     */
    public function __construct(
        protected string $usingDto
    ) {
        //
    }

    /**
     * Dynamically handle calls to set the import type for the DTO.
     *
     * @param  string  $name
     * @param  array  $arguments
     * @return class-string<TDto>
     */
    public function __call(string $name, array $arguments)
    {
        if (! str_starts_with($name, 'to')) {
            throw new \BadMethodCallException("Method {$name} does not start with 'to'.");
        }

        $this->usingDto::setImportType(
            type: $name,
            manual: true,
            args: $arguments,
        );

        return $this->usingDto;
    }

    /**
     * Dynamically handle attempts to get properties that are not defined.
     *
     * @param  string  $name
     * @return mixed
     */
    public function __get(string $name)
    {
        throw new \BadMethodCallException("Property does not support for casting to DTO: {$name}. Use a method instead starting with 'to'.");
    }
}

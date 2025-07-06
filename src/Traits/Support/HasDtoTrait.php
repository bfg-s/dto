<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits\Support;

use Bfg\Dto\Attributes\DtoClass;
use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Dto;
use Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException;

/**
 * Trait HasDtoTrait
 *
 * This trait is used to mark classes that are DTOs (Data Transfer Objects).
 *
 * @package Bfg\Dto\Traits\Support
 * @template TDto of Dto<mixed>
 */
trait HasDtoTrait
{
    /**
     * Get the DTO instance from the class.
     *
     * @return TDto|DtoCollection<int, TDto>
     */
    public function getDto(): Dto|DtoCollection
    {
        /** @var class-string<Dto>|null $dataClass */
        $dataClass = match (true) {
            property_exists($this, 'dtoClass') => $this->dtoClass,
            method_exists($this, 'dtoClass') => $this->dtoClass(),
            default => (function () {
                $reflection = new \ReflectionClass(static::class);
                $attributes = $reflection->getAttributes(DtoClass::class);
                if (count($attributes) > 0) {
                    /** @var DtoClass $attribute */
                    $attribute = $attributes[0]->newInstance();
                    return $attribute->class;
                }
                return null;
            })(),
        };

        if ($dataClass === null) {
            throw new \RuntimeException(
                sprintf(
                    'The dto class is not defined in %s. Please define it using the "dtoClass" property or method, or by using the #[DtoClass] attribute.',
                    static::class
                )
            );
        }

        if (! is_a($dataClass, Dto::class, true)) {
            throw new \RuntimeException(
                sprintf(
                    'The dto class "%s" must be an instance of %s',
                    $dataClass,
                    Dto::class
                )
            );
        }

        return $dataClass::from($this);
    }
}

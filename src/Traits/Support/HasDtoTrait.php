<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits\Support;

use Bfg\Dto\Attributes\DtoClass;
use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Dto;

/**
 * Trait HasDtoTrait
 *
 * This trait is used to mark classes that are DTOs (Data Transfer Objects).
 *
 * @package Bfg\Dto\Traits\Support
 * @template TDto of \Bfg\Dto\Dto<mixed>
 */
trait HasDtoTrait
{
    /**
     * Get the DTO instance from the class.
     *
     * @return TDto
     */
    public function getDto(): Dto
    {
        $dtoClass = $this->getDtoClass();
        $dto = $dtoClass::from($this);
        if (! ($dto instanceof Dto)) {
            throw new \BadFunctionCallException('The DTO class must return a single instance of Dto, not a collection. Use the `getDtoCollection` method instead.');
        }
        return $dto;
    }

    /**
     * Get the DTO collection from the class.
     *
     * @return \Bfg\Dto\Collections\DtoCollection<int, TDto>
     */
    public function getDtoCollection(): DtoCollection
    {
        $dtoClass = $this->getDtoClass();
        $dtoCollection = $dtoClass::from($this);
        if (! ($dtoCollection instanceof DtoCollection)) {
            throw new \BadFunctionCallException('The DTO class must return a collection of Dto, not a single instance. Use the `getDto` method instead.');
        }
        return $dtoCollection;
    }

    /**
     * Get the DTO instance or collection from the class.
     *
     * @return TDto|\Bfg\Dto\Collections\DtoCollection<int, TDto>
     */
    public function getMixedDto(): Dto|DtoCollection
    {
        $dtoClass = $this->getDtoClass();
        return $dtoClass::from($this);
    }

    /**
     * Get the DTO class name from the class.
     *
     * @return class-string<Dto>
     */
    public function getDtoClass(): string
    {
        /** @var class-string<Dto>|null $dtoClass */
        $dtoClass = match (true) {
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

        if ($dtoClass === null) {
            throw new \RuntimeException(
                sprintf(
                    'The dto class is not defined in %s. Please define it using the "dtoClass" property or method, or by using the #[DtoClass] attribute.',
                    static::class
                )
            );
        }

        if (! is_a($dtoClass, Dto::class, true)) {
            throw new \RuntimeException(
                sprintf(
                    'The dto class "%s" must be an instance of %s',
                    $dtoClass,
                    Dto::class
                )
            );
        }

        return $dtoClass;
    }
}

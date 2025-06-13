<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Dto;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

trait DtoCastUsingTrait
{
    /**
     * Specify the collection for the cast.
     *
     * @param  'url'|'serializeDto'|'serializeAny'|'json'  $storeType
     * @param  mixed|null  $source
     * @return string
     */
    public static function as(string $storeType, mixed $source = null): string
    {
        static::$__importType[static::class] = [
            'type' => $storeType,
            'source' => $source,
        ];

        return static::class;
    }

    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<Dto|DtoCollection, iterable>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class(static::class, static::$source) implements CastsAttributes
        {
            /**
             * @param  class-string<Dto>  $class
             * @param  string|null  $source
             */
            public function __construct(
                protected string $class,
                protected string|null $source,
            ) {
            }

            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key]) || ! $attributes[$key]) {
                    return null;
                }

                $this->class = $this->class::discoverCastedDto($model, $key, $value, $attributes);

                return $this->class::fromAnything(dto_string_replace($attributes[$key], $model));
            }

            public function set($model, $key, $value, $attributes): array
            {
                if ($value instanceof Dto || $value instanceof DtoCollection) {
                    return [$key => $this->source ?: (method_exists($value, 'toImport')
                        ? $value->toImport()
                        : $value->toJson(JSON_UNESCAPED_UNICODE)
                    )];
                } elseif (is_string($value)) {
                    return [$key => $value];
                }
                return [$key => $value];
            }
        };
    }
}

<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Dto;
use Bfg\Dto\Traps\DtoCastingStoreTrap;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Str;

trait DtoCastUsingTrait
{
    /**
     * Specify the collection for the cast.
     *
     * @return \Bfg\Dto\Traps\DtoCastingStoreTrap|static
     */
    public static function store(): DtoCastingStoreTrap|static
    {
        return new DtoCastingStoreTrap(static::class);
    }

    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<Dto|DtoCollection, iterable>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class(static::class, static::$__source) implements CastsAttributes
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

                $data = dto_string_replace($attributes[$key], $model, '{{ * }}');

                if (
                    ($importType = $this->class::getImportType())
                    && str_starts_with($importType['type'], 'to')
                ) {
                    $methodSuffix = Str::studly(substr($importType['type'], 2));
                    $method = 'from'.$methodSuffix;
                    if (method_exists($this->class, $method)) {
                        $result = call_user_func([$this->class, $method], $data, $model, $key, $attributes);
                        if ($result instanceof $this->class) {
                            return $result;
                        } else {
                            return $this->class::from($result, $model);
                        }
                    }
                }

                return $this->class::from($data, $model);
            }

            public function set($model, $key, $value, $attributes): array
            {
                if ($value instanceof Dto || $value instanceof DtoCollection) {
                    return [$key => $this->source ?: (method_exists($value, 'toImport')
                        ? $value->toImport()
                        : $value->toJson(JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT)
                    )];
                } elseif (is_string($value)) {
                    return [$key => $value];
                } elseif (is_array($value) || is_object($value)) {
                    return [$key => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT)];
                }
                return [$key => $value];
            }
        };
    }
}

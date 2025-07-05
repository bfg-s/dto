<?php

namespace Bfg\Dto\Traits;

use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Dto;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait DtoCollectionMethodsTrait
{
    protected array $meta = [];

    /**
     * @var array
     */
    protected static array $__importType = [];

    /**
     * @var array
     */
    protected static array $__roots = [];

    /**
     * @var array
     */
    protected static array $__models = [];

    /**
     * Set import type for the DTO
     *
     * @param  string  $type
     * @param  mixed|null  $source
     * @param  \Bfg\Dto\Collections\DtoCollection|null  $instance
     * @return void
     */
    public static function setImportType(string $type, mixed $source = null, DtoCollection|null $instance = null): void
    {
        static::$__importType[static::class] = compact('type', 'source', 'instance');
        if ($instance) {
            $instanceId = spl_object_id($instance);
            static::$__importType[$instanceId] = static::$__importType[static::class];
        }
    }

    /**
     * Get an import type for the DTO
     *
     * @param  \Bfg\Dto\Collections\DtoCollection|null  $instance
     * @return array
     */
    public static function getImportType(DtoCollection|null $instance = null): array
    {
        if ($instance) {
            $instanceId = spl_object_id($instance);
            if (isset(static::$__importType[$instanceId])) {
                return static::$__importType[$instanceId];
            }
        }
        return static::$__importType[static::class] ?? [
            'type' => 'json',
            'source' => null,
            'instance' => $instance ?? null,
        ];
    }

    /**
     * @return string
     * @throws \JsonException
     */
    public function toImport(): string
    {
        $data = $this->all();
        foreach ($data as $key => $dto) {
            if ($dto instanceof Dto) {
                $data[$key] = $dto->toImport();
            } elseif ($dto instanceof DtoCollection) {
                $data[$key] = $dto->toImport();
            } elseif (is_array($dto)) {
                $data[$key] = json_encode($dto, JSON_UNESCAPED_UNICODE);
            } else {
                $data[$key] = $dto;
            }
            if (Dto::isJson($data[$key])) {
                $data[$key] = json_decode($data[$key], true, 512, JSON_THROW_ON_ERROR);
            }
        }
        return json_encode($data);
    }

    /**
     * Specify the collection for the cast.
     *
     * @param  class-string  $dtoClass
     * @return non-empty-string
     */
    public static function using(string $dtoClass): string
    {
        return static::class.':'.$dtoClass;
    }

    public static function setModelFor(string $class, $model): void
    {
        static::$__models[$class] = $model;
    }

    public static function getModelFor(string $class): string|null
    {
        return static::$__models[$class] ?? null;
    }

    /**
     * Get the name of the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return CastsAttributes
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        $collectionClass = static::class;
        return new class($arguments, $collectionClass) implements CastsAttributes
        {
            /**
             * @param  array  $arguments
             * @param  class-string<DtoCollection>  $collectionClass
             */
            public function __construct(
                protected array $arguments,
                protected string $collectionClass,
            ) {
            }

            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key])) {
                    return new $this->collectionClass;
                }

                $data = $attributes[$key];

                $dtoClass = $this->arguments[0] ?? null;

                if (! is_a($dtoClass, Dto::class, true)) {
                    throw new \InvalidArgumentException('A DTO class must be provided. Use the `using` method to specify it.');
                }

                $this->collectionClass::setModelFor($dtoClass, $model);

                if (
                    ($importType = $dtoClass::getImportType())
                    && str_starts_with($importType['type'], 'to')
                ) {
                    $methodSuffix = Str::studly(substr($importType['type'], 2));
                    $method = 'from'.$methodSuffix;
                    if (method_exists($dtoClass, $method)) {
                        $result = call_user_func([$dtoClass, $method], $data, $model, $key, $attributes);
                        if ($result instanceof DtoCollection) {
                            return $result;
                        } else {
                            $dtoCollection = $dtoClass::from($result, $model);
                        }
                    }
                }

                $dtoCollection = !isset ($dtoCollection) ? $dtoClass::from($data, $model) : $dtoCollection;

                if (! ($dtoCollection instanceof $this->collectionClass)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'The structure of the DTO collection is not collection %s: %s',
                            $this->collectionClass,
                            $data
                        )
                    );
                }

                return $dtoCollection;
            }

            public function set($model, $key, $value, $attributes): array
            {
                return [
                    $key => $value instanceof Dto || $value instanceof DtoCollection
                        ? $value->toImport()
                        : (is_array($value) || is_object($value)
                            ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT)
                            : $value)
                ];
            }
        };
    }

    public function setRoot(string $class): static
    {
        static::$__roots[static::class][spl_object_id($this)] = $class;
        static::$__roots[static::class]['last'] = $class;
        return $this;
    }

    /**
     * @return class-string<Dto>|null
     */
    public function getRoot(): ?string
    {
        return static::$__roots[static::class][spl_object_id($this)] ?? (
            $this->first()
                ? get_class($this->first())
                : (static::$__roots[static::class]['last'] ?? null)
        );
    }

    /**
     * @param $item
     * @return $this
     * @throws \Bfg\Dto\Exceptions\DtoUndefinedArrayKeyException
     */
    public function add($item): static
    {
        if (! ($item instanceof Dto)) {
            if ($root = $this->getRoot()) {
                $model = static::getModelFor($root);
                $item = $root::from($item, $model);
            } else {
                throw new \BadMethodCallException('You can add only dto objects or supported formats with set root.');
            }
        }
        $this->items[] = $item;
        return $this;
    }

    /**
     * Save all items to the database.
     *
     * @param  string  $table
     * @return bool
     */
    public function insertToDatabase(string $table): bool
    {
        return DB::table($table)->insert($this->toArray());
    }

    /**
     * Save all items to the model.
     *
     * @param  string  $model
     * @return bool
     */
    public function insertToModel(string $model): bool
    {
        if (! is_subclass_of($model, \Illuminate\Database\Eloquent\Model::class)) {
            return false;
        }
        return $model::query()->insert($this->toArray());
    }

    public function __call($method, $parameters)
    {
        $first = $this->first();

        if (is_object($first) && method_exists($first, $method)) {
            return $this->map(function ($item) use ($method, $parameters) {
                return $item->{$method}(...$parameters);
            });
        }

        if (! static::hasMacro($method)) {
            throw new \BadMethodCallException(sprintf(
                'Method %s::%s does not exist.',
                static::class,
                $method
            ));
        }

        $macro = static::$macros[$method];

        if ($macro instanceof \Closure) {
            $macro = $macro->bindTo($this, static::class);
        }

        return $macro(...$parameters);
    }

    public function __serialize(): array
    {
        return $this->map(function (Dto $item) {
            return $item->toSerialize();
        })->merge(['__meta' => $this->getMeta()])->toArray();
    }

    public function __unserialize(array $data): void
    {
        if (array_key_exists('__meta', $data)) {
            $this->setMeta($data['__meta']);
            unset($data['__meta']);
        }

        $this->items = array_map(function ($item) {
            return unserialize($item);
        }, $data);
    }

    public function toSerialize(): string
    {
        return serialize($this);
    }

    public function setMeta(array $meta): static
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    public function unsetMeta(string $key): static
    {
        unset($this->meta[$key]);

        return $this;
    }

    public function getMeta(string $key = null): mixed
    {
        return $key ? ($this->meta[$key] ?? null) : $this->meta;
    }
}

<?php

namespace Bfg\Dto\Traits;

use Bfg\Dto\Dto;
use Illuminate\Support\Facades\DB;

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
     * Set import type for the DTO
     *
     * @param  string  $type
     * @param  array  $options
     * @return void
     */
    public function setImportType(string $type, array $options = []): void
    {
        static::$__importType[static::class][spl_object_id($this)] = compact('type', 'options');
    }

    /**
     * Get an import type for the DTO
     *
     * @return array|null
     */
    public function getImportType(): array|null
    {
        return static::$__importType[static::class][spl_object_id($this)] ?? null;
    }

    /**
     * Convert an object to the format string from which the object was created.
     * Used for storing in a database.
     *
     * @return string|null
     */
    public function toImport(): string|null
    {
        $importType = $this->getImportType();
        $type = data_get($importType, 'type');
        if ($type === 'url') {
            return data_get($importType, 'options.url');
        } else if ($type === 'serializeDto') {
            return $this->map->toSerialize()->toJson(JSON_UNESCAPED_UNICODE);
        } else if ($type === 'serializeAny') {
            return serialize($this->toArray());
        } else {
            return $this->toJson(JSON_UNESCAPED_UNICODE);
        }
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
                $item = $root::fromAnything($item);
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

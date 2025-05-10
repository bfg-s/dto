<?php

namespace Bfg\Dto\Traits;

use Bfg\Dto\Dto;
use Illuminate\Support\Facades\DB;

trait DtoCollectionMethodsTrait
{
    protected array $meta = [];

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

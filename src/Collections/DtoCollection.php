<?php

namespace Bfg\Dto\Collections;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DtoCollection extends Collection
{
    /**
     * Save all items to the database.
     *
     * @param  string  $table
     * @return bool
     */
    public function saveToDatabase(string $table): bool
    {
        return DB::table($table)->insert($this->toArray());
    }

    /**
     * Save all items to the model.
     *
     * @param  string  $model
     * @return bool
     */
    public function saveToModel(string $model): bool
    {
        if (! is_subclass_of($model, \Illuminate\Database\Eloquent\Model::class)) {
            return false;
        }
        return $model::query()->insert($this->toArray());
    }
}

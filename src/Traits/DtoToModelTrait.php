<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\BlessModel\Facades\BlessModel;
use Illuminate\Database\Eloquent\Model;

trait DtoToModelTrait
{
    /**
     * Convert dto to model
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return \Illuminate\Database\Eloquent\Model|mixed
     */
    public function toModel(Model|string $model): mixed
    {
        if (class_exists(BlessModel::class)) {
            return BlessModel::do($model, $this->toArray());
        }

        if (is_string($model)) {
            $model = new $model();
        }

        $data = $this->toArray();

        foreach ($data as $key => $value) {
            if (in_array($key, $model->getFillable())) {
                $model->{$key} = $value;
            }
        }

        return $model;
    }
}

<?php

declare(strict_types=1);

namespace Bfg\Dto\Tests;

include_once __DIR__.'/Test3Dto.php';

use Bfg\Dto\Collections\DtoCollection;
use Illuminate\Database\Eloquent\Model;

class User5Model extends Model
{
    protected $fillable = [
        'test5',
    ];

    protected function casts(): array
    {
        return [
            'test5' => DtoCollection::using(Test3Dto::class),
        ];
    }
}

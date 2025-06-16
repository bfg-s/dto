<?php

declare(strict_types=1);

namespace Bfg\Dto\Tests;

include_once __DIR__.'/TestDto.php';

use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Tests\TestDto;
use Illuminate\Database\Eloquent\Model;

class User4Model extends Model
{
    protected $fillable = [
        'test4',
    ];

    protected function casts(): array
    {
        return [
            'test4' => DtoCollection::using(TestDto::store()->toDatabase()),
        ];
    }
}

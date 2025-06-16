<?php

declare(strict_types=1);

namespace Bfg\Dto\Tests;

include_once __DIR__.'/TestDto.php';

use Bfg\Dto\Tests\TestDto;
use Illuminate\Database\Eloquent\Model;

class User2Model extends Model
{
    protected $fillable = [
        'test2',
    ];

    protected function casts(): array
    {
        return [
            'test2' => TestDto::store()->toDatabase(),
        ];
    }
}

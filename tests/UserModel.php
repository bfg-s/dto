<?php

declare(strict_types=1);

namespace Bfg\Dto\Tests;

include_once __DIR__.'/TestDto.php';

use Bfg\Dto\Tests\TestDto;
use Illuminate\Database\Eloquent\Model;

class UserModel extends Model
{
    protected $fillable = [
        'test',
    ];

    protected function casts(): array
    {
        return [
            'test' => TestDto::class,
        ];
    }
}

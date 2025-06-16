<?php

declare(strict_types=1);

namespace Bfg\Dto\Tests;

include_once __DIR__.'/TestDto.php';

use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Tests\TestDto;
use Illuminate\Database\Eloquent\Model;

class User3Model extends Model
{
    protected $fillable = [
        'test3',
    ];

    protected function casts(): array
    {
        return [
            'test3' => DtoCollection::using(TestDto::class),
        ];
    }
}

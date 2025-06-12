<?php

namespace Bfg\Dto\Tests;

use Bfg\Dto\Attributes\DtoItem;
use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Dto;

include_once __DIR__.'/Test2Dto.php';

class TestDto extends Dto
{
    protected static array $extends = [
        'id' => ['int', 'null']
    ];

    protected static array $cast = [
        'number' => 'int',
    ];

    /**
     * @param  int|null  $number
     * @param  string  $name
     * @param  string|null  $email
     * @param  \Bfg\Dto\Tests\Test2Dto  $test
     * @param  \Bfg\Dto\Collections\DtoCollection<int, Test2Dto>  $collect
     * @param  \Bfg\Dto\Tests\Test2Dto|array  $tests
     */
    public function __construct(
        public int|null $number,
        public string $name,
        public string|null $email,
        public Test2Dto $test,
        #[DtoItem(Test2Dto::class)] public DtoCollection $collect,
        public Test2Dto|array $tests = [],
    ) {
    }
}

<?php
namespace Bfg\Dto\Tests;

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

    public function __construct(
        public int|null $number,
        public string $name,
        public string|null $email,
        public Test2Dto $test,
        public Test2Dto|array $tests,
    ) {}
}

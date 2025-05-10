<?php

namespace Bfg\Dto\Tests\Unit;

use Bfg\Dto\Tests\TestDto;
use PHPUnit\Framework\TestCase;

include_once __DIR__.'/../TestDto.php';
include_once __DIR__.'/../Test2Dto.php';

class DtoTypesTest extends TestCase
{
    public function test_types(): void
    {
        $array = [
            'name' => 'name',
            'email' => 'email',
            'test' => [
                'name' => 'name',
                'email' => 'email',
            ],
            'id' => '1',
        ];

        $dto = TestDto::fromArray($array);

        $this->assertTrue($dto->isInt('id'));
        $this->assertTrue($dto->isArray('tests'));
        $this->assertTrue($dto->isObject('test'));
    }

    public function test_casting()
    {
        $array = [
            'number' => '22',
            'name' => 'name',
            'email' => 'email',
            'test' => [
                'name' => 'name',
                'email' => 'email',
            ],
            'tests' => [
                [
                    'name' => 'name',
                    'email' => 'email',
                ],
                [
                    'name' => 'name',
                    'email' => 'email',
                ],
            ],
            'id' => '1',
        ];

        $dto = TestDto::fromArray($array);

        $this->assertTrue($dto->isInt('number'));
        $this->assertTrue($dto->isInt('id'));
        $this->assertTrue($dto->number === 22);
        $this->assertTrue($dto->id === 1);
    }
}

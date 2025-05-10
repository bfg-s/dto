<?php

namespace Bfg\Dto\Tests\Unit;

use Bfg\Dto\Tests\TestDto;
use PHPUnit\Framework\TestCase;

include_once __DIR__.'/../TestDto.php';
include_once __DIR__.'/../Test2Dto.php';

class DtoEventsTest extends TestCase
{
    public function test_on_creating(): void
    {
        $array = [
            'number' => 22,
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
            'id' => 1,
        ];

        TestDto::on('creating', function (array $arguments) {
            $arguments['name'] = 'John Doe';
            return $arguments;
        });

        $dto = TestDto::fromArray($array);

        $this->assertEquals('John Doe', $dto->name);
    }

    public function test_on_created(): void
    {
        $array = [
            'number' => 22,
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
            'id' => 1,
        ];

        TestDto::on('created', function (TestDto $dto, array $arguments) {
            $dto->name = 'John Doe';
        });

        $dto = TestDto::fromArray($array);

        $this->assertEquals('John Doe', $dto->name);
    }

    public function test_on_updating_name(): void
    {
        $array = [
            'number' => 22,
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
            'id' => 1,
        ];

        TestDto::on(['updating', 'name'], function (mixed $value, TestDto $dto) {
            return strtoupper($value);
        });

        $dto = TestDto::fromArray($array);

        $dto->set('name', 'John Doe');

        $this->assertEquals('JOHN DOE', $dto->name);
    }

    public function test_on_updated_name(): void
    {
        $array = [
            'number' => 22,
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
            'id' => 1,
        ];

        TestDto::on(['updated', 'name'], function (TestDto $dto) {
            $dto->name = strtoupper($dto->name);
        });

        $dto = TestDto::fromArray($array);

        $dto->set('name', 'John Doe');

        $this->assertEquals('JOHN DOE', $dto->name);
    }
}

<?php

namespace Bfg\Dto\Tests\Unit;

use Bfg\Dto\Tests\TestDto;
use PHPUnit\Framework\TestCase;

include_once __DIR__.'/../TestDto.php';
include_once __DIR__.'/../Test2Dto.php';

class DtoToTest extends TestCase
{
    public function test_to_array(): void
    {
        $array = [
            'number' => 22,
            'name' => 'name',
            'email' => 'email',
            'test' => [
                'name' => 'name',
                'email' => 'email',
            ],
            "collect" => [],
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

        TestDto::clearEvents();
        TestDto::clearGlobalEvents();

        $dto = TestDto::fromArray($array);

        $this->assertTrue($dto->toArray() === $array);
    }

    public function test_to_json()
    {
        $array = [
            'number' => 22,
            'name' => 'name',
            'email' => 'email',
            'test' => [
                'name' => 'name',
                'email' => 'email',
            ],
            "collect" => [],
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

        TestDto::clearEvents();
        TestDto::clearGlobalEvents();

        $dto = TestDto::fromArray($array);

        $this->assertTrue($dto->toJson() === json_encode($array));
    }

    public function test_to_serialize()
    {
        $array = [
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

        TestDto::clearEvents();
        TestDto::clearGlobalEvents();

        $dtoGeneral = TestDto::fromArray($array);

        $dtoGeneral->setMeta(['test' => 'meta-saven-in-serialize-memory']);

        $dto = TestDto::fromSerialize($dtoGeneral->toSerialize());

        $this->assertTrue($dtoGeneral->equals($dto));
        $this->assertTrue($dto->getMeta('test') === 'meta-saven-in-serialize-memory');
    }

    public function test_to_string()
    {
        $array = [
            'number' => 22,
            'name' => 'name',
            'email' => 'email',
            'test' => [
                'name' => 'name',
                'email' => 'email',
            ],
            "collect" => [],
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

        TestDto::clearEvents();
        TestDto::clearGlobalEvents();

        $dtoGeneral = TestDto::fromArray($array);
        $this->assertTrue($dtoGeneral->toJson() === json_encode($array));
    }
}

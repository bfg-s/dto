<?php

namespace Bfg\Dto\Tests\Feature;

use Bfg\Dto\Tests\TestDto;
use Tests\TestCase;

include_once __DIR__.'/../TestDto.php';
include_once __DIR__.'/../Test2Dto.php';

class DtoToTest extends TestCase
{
    public function test_to_response()
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

        $this->assertTrue($dto->toResponse()->getContent() === json_encode(['data' => $array]));
    }
}

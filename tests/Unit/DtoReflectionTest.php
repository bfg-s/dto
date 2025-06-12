<?php

namespace Bfg\Dto\Tests\Unit;

use Bfg\Dto\Tests\TestDto;
use PHPUnit\Framework\TestCase;

include_once __DIR__.'/../TestDto.php';

class DtoReflectionTest extends TestCase
{
    public function test_reflection_explain(): void
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

        TestDto::clearEvents();
        TestDto::clearGlobalEvents();

        $dto = TestDto::fromArray($array);

        $explain = $dto->explain();

        $this->assertTrue($explain->name === TestDto::class);
        $this->assertTrue($explain->ver === '1.0');
        $this->assertTrue($explain->isArray('properties'));
    }

    public function test_reflection_vars(): void
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

        TestDto::clearEvents();
        TestDto::clearGlobalEvents();

        $dto = TestDto::fromArray($array);

        $vars = $dto->vars();

        $this->assertTrue($vars['number'] === 22);
        $this->assertTrue($vars['name'] === 'name');
        $this->assertTrue($vars['email'] === 'email');
    }

    public function test_reflection_get_names(): void
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

        TestDto::clearEvents();
        TestDto::clearGlobalEvents();

        $dto = TestDto::fromArray($array);

        $names = $dto->getNames();

        $this->assertTrue($names === ['number', 'name', 'email', 'test', 'collect', 'tests', 'id']);
    }
}

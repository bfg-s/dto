<?php

namespace Bfg\Dto\Tests\Unit;

use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Tests\SettingsDto;
use Bfg\Dto\Tests\Test2Dto;
use Bfg\Dto\Tests\TestDto;
use PHPUnit\Framework\TestCase;

include_once __DIR__.'/../TestDto.php';
include_once __DIR__.'/../Test2Dto.php';
include_once __DIR__.'/../SettingsDto.php';

class DtoConstructorTest extends TestCase
{
    public function test_from_empty(): void
    {
        $dto = TestDto::fromEmpty();

        $this->assertTrue($dto->name === '');
        $this->assertTrue($dto->email === null);
        $this->assertTrue($dto->test instanceof Test2Dto);
        $this->assertTrue($dto->test->name === '');
        $this->assertTrue($dto->test->email === null);
        $this->assertTrue($dto->tests === []);
    }

    public function test_from_array()
    {
        $dto = TestDto::fromArray([
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
        ]);

        $this->assertTrue($dto->name === 'name');
        $this->assertTrue($dto->email === 'email');
        $this->assertTrue($dto->test instanceof Test2Dto);
        $this->assertTrue($dto->test->name === 'name');
        $this->assertTrue($dto->test->email === 'email');
        $this->assertTrue($dto->tests[0] instanceof Test2Dto);
        $this->assertTrue($dto->tests[0]->name === 'name');
        $this->assertTrue($dto->tests[0]->email === 'email');
        $this->assertTrue($dto->tests[1] instanceof Test2Dto);
        $this->assertTrue($dto->tests[1]->name === 'name');
        $this->assertTrue($dto->tests[1]->email === 'email');
    }

    public function test_from_json()
    {
        $dto = TestDto::fromJson('{"name":"name","email":"email","test":{"name":"name","email":"email"},"tests":[{"name":"name","email":"email"},{"name":"name","email":"email"}]}');

        $this->assertTrue($dto->name === 'name');
        $this->assertTrue($dto->email === 'email');
        $this->assertTrue($dto->test instanceof Test2Dto);
        $this->assertTrue($dto->test->name === 'name');
        $this->assertTrue($dto->test->email === 'email');
        $this->assertTrue($dto->tests[0] instanceof Test2Dto);
        $this->assertTrue($dto->tests[0]->name === 'name');
        $this->assertTrue($dto->tests[0]->email === 'email');
        $this->assertTrue($dto->tests[1] instanceof Test2Dto);
        $this->assertTrue($dto->tests[1]->name === 'name');
        $this->assertTrue($dto->tests[1]->email === 'email');
    }

    public function test_from_serialize()
    {
        $dto = TestDto::fromArray([
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
        ]);

        $dto->setMeta(['test' => 'meta-saven-in-serialize-memory']);

        $dto = TestDto::fromSerialize($dto->toSerialize());

        $this->assertTrue($dto->name === 'name');
        $this->assertTrue($dto->email === 'email');
        $this->assertTrue($dto->test instanceof Test2Dto);
        $this->assertTrue($dto->test->name === 'name');
        $this->assertTrue($dto->test->email === 'email');
        $this->assertTrue($dto->tests[0] instanceof Test2Dto);
        $this->assertTrue($dto->tests[0]->name === 'name');
        $this->assertTrue($dto->tests[0]->email === 'email');
        $this->assertTrue($dto->tests[1] instanceof Test2Dto);
        $this->assertTrue($dto->tests[1]->name === 'name');
        $this->assertTrue($dto->tests[1]->email === 'email');
        $this->assertTrue($dto->getMeta('test') === 'meta-saven-in-serialize-memory');
    }

    public function test_from_static_cache()
    {
        $dto = TestDto::fromStaticCache('test', function () {
            return TestDto::fromArray([
                'name' => 'John Doe',
                'email' => 'test@gmail.com',
                'test' => [
                    'name' => 'John Doe',
                    'email' => 'test2@gmail.com',
                ],
                'collect' => [
                    [
                        'name' => 'John Doe',
                        'email' => 'test3@gmail.com',
                    ]
                ]
            ]);
        });

        $this->assertTrue($dto->name === 'John Doe');
        $this->assertTrue($dto->email === 'test@gmail.com');
        $this->assertTrue($dto->test instanceof Test2Dto);
        $this->assertTrue($dto->test->name === 'John Doe');
        $this->assertTrue($dto->test->email === 'test2@gmail.com');
        $this->assertTrue($dto->tests === []);
        $this->assertTrue($dto->collect->isNotEmpty());
        $this->assertTrue($dto->collect->first()->email === 'test3@gmail.com');
    }

    public function test_from_collection()
    {
        $dto = TestDto::fromCollection([
            ['name' => 'John Doe', 'email' => 'test@gmail.com', 'test' => ['name' => 'John Doe', 'email' => 'test2@gmail.com']],
            ['name' => 'John Doe', 'email' => 'test@gmail.com', 'test' => ['name' => 'John Doe', 'email' => 'test2@gmail.com']],
        ]);

        $this->assertTrue($dto instanceof DtoCollection);
        $this->assertTrue($dto[0] instanceof TestDto);
        $this->assertTrue($dto[0]->name === 'John Doe');
        $this->assertTrue($dto[0]->email === 'test@gmail.com');
        $this->assertTrue($dto[0]->test instanceof Test2Dto);
        $this->assertTrue($dto[0]->test->name === 'John Doe');
        $this->assertTrue($dto[0]->test->email === 'test2@gmail.com');
        $this->assertTrue($dto[1] instanceof TestDto);
        $this->assertTrue($dto[1]->name === 'John Doe');
        $this->assertTrue($dto[1]->email === 'test@gmail.com');
        $this->assertTrue($dto[1]->test instanceof Test2Dto);
        $this->assertTrue($dto[1]->test->name === 'John Doe');
        $this->assertTrue($dto[1]->test->email === 'test2@gmail.com');
    }

    public function test_from_anything()
    {
        $dto = TestDto::from([
            'name' => 'John Doe',
            'email' => 'test@gmail.com',
            'test' => json_encode([
                'name' => 'John Doe',
                'email' => 'test2gmail.com',
            ]),
        ]);

        $this->assertTrue($dto->name === 'John Doe');

        $dto = TestDto::from('{"name":"John Doe","email":"test@gmail.com","test":{"name":"John Doe","email":"test@gmail.com"}}');

        $this->assertTrue($dto->name === 'John Doe');
        $this->assertTrue($dto->email === 'test@gmail.com');

        $dto = TestDto::from($dto->toSerialize());

        $this->assertTrue($dto->name === 'John Doe');
        $this->assertTrue($dto->email === 'test@gmail.com');
    }

    public function test_from_new()
    {
        $dto = TestDto::new(
            name: 'John Doe',
            email: 'test@gmail.com',
            test: [
                'name' => 'John Doe',
                'email' => 'test@gmail.com',
            ]
        );

        $this->assertTrue($dto->name === 'John Doe');
        $this->assertTrue($dto->email === 'test@gmail.com');
        $this->assertTrue($dto->test instanceof Test2Dto);
    }

    public function test_from_mutator()
    {
        $dto = SettingsDto::fromAssoc([
            'receive_notifications' => 'True'
        ]);

        $this->assertTrue($dto->receiveNotifications);

        $array = $dto->toArray();

        $this->assertTrue($array['receive_notifications'] === 'True');

        $dto = SettingsDto::fromAssoc([
            'receive_notifications' => 'False'
        ]);

        $this->assertFalse($dto->receiveNotifications);

        $array = $dto->toArray();

        $this->assertTrue($array['receive_notifications'] === 'False');
    }
}

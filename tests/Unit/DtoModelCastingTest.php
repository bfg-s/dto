<?php

namespace Bfg\Dto\Tests\Unit;

use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Tests\Test3Dto;
use Bfg\Dto\Tests\TestDto;
use Bfg\Dto\Tests\User2Model;
use Bfg\Dto\Tests\User3Model;
use Bfg\Dto\Tests\User4Model;
use Bfg\Dto\Tests\User5Model;
use Bfg\Dto\Tests\UserModel;
use PHPUnit\Framework\TestCase;

include_once __DIR__.'/../UserModel.php';
include_once __DIR__.'/../User2Model.php';
include_once __DIR__.'/../User3Model.php';
include_once __DIR__.'/../User4Model.php';
include_once __DIR__.'/../User5Model.php';
include_once __DIR__.'/../TestDto.php';
include_once __DIR__.'/../Test3Dto.php';

class DtoModelCastingTest extends TestCase
{
    public function test_simple_model_casting(): void
    {
        $user = new UserModel();
        $user->setRawAttributes([
            'test' => json_encode([
                'number' => 1,
                'name' => 'John Doe',
                'email' => 'test@gmail.com',
                'test' => [
                    'id' => 1,
                    'name' => 'Test2',
                ],
                'collect' => [
                    ['id' => 1, 'name' => 'Test2'],
                    ['id' => 2, 'name' => 'Test3'],
                ],
                'tests' => [
                    ['id' => 1, 'name' => 'Test2'],
                    ['id' => 2, 'name' => 'Test3'],
                ],
            ]),
        ]);

        $this->assertInstanceOf(TestDto::class, $user->test);
        $this->assertTrue($user->test->number === 1);
        $this->assertTrue($user->test->name === 'John Doe');
        $this->assertTrue($user->test->email === 'test@gmail.com');
    }

    public function test_manual_model_casting(): void
    {
        $user = new User2Model();
        $user->setRawAttributes([
            'test2' => json_encode([
                'number' => 1,
                'name' => 'John Doe',
                'email' => 'test@gmail.com',
                'test' => [
                    'id' => 1,
                    'name' => 'Test2',
                ],
                'collect' => [
                    ['id' => 1, 'name' => 'Test2'],
                    ['id' => 2, 'name' => 'Test3'],
                ],
                'tests' => [
                    ['id' => 1, 'name' => 'Test2'],
                    ['id' => 2, 'name' => 'Test3'],
                ],
            ]),
        ]);
        $this->assertTrue($user->test2->number === 2);
        $this->assertInstanceOf(TestDto::class, $user->test2);
        $user->test2->name = 'Changed Name';
        $refUser = new \ReflectionClass($user);
        $method = $refUser->getMethod('mergeAttributesFromCachedCasts');
        $method->setAccessible(true);
        $method->invoke($user);
        $this->assertTrue(str_contains($user->syncOriginal()->getRawOriginal('test2'), '"number":13'));
    }

    public function test_simple_collect_model_casting(): void
    {
        $user = new User3Model();

        $user->setRawAttributes([
            'test3' => json_encode([[
                'number' => 1,
                'name' => 'John Doe',
                'email' => 'test@gmail.com',
                'test' => [
                    'id' => 1,
                    'name' => 'Test2',
                ],
                'collect' => [
                    ['id' => 1, 'name' => 'Test2'],
                    ['id' => 2, 'name' => 'Test3'],
                ],
                'tests' => [
                    ['id' => 1, 'name' => 'Test2'],
                    ['id' => 2, 'name' => 'Test3'],
                ],
            ]]),
        ]);

        $this->assertInstanceOf(DtoCollection::class, $user->test3);
        $this->assertTrue($user->test3->isNotEmpty());
        $this->assertInstanceOf(TestDto::class, $user->test3->first());
        $this->assertTrue($user->test3->first()->number === 2);
        $this->assertTrue($user->test3->first()->name === 'John Doe');
        $this->assertTrue($user->test3->first()->email === 'test@gmail.com');
    }

    public function test_manual_collect_model_casting(): void
    {
        $user = new User4Model();

        $user->setRawAttributes([
            'test4' => json_encode([[
                'number' => 1,
                'name' => 'John Doe',
                'email' => 'test@gmail.com',
                'test' => [
                    'id' => 1,
                    'name' => 'Test2',
                ],
                'collect' => [
                    ['id' => 1, 'name' => 'Test2'],
                    ['id' => 2, 'name' => 'Test3'],
                ],
                'tests' => [
                    ['id' => 1, 'name' => 'Test2'],
                    ['id' => 2, 'name' => 'Test3'],
                ],
            ]]),
        ]);

        $this->assertInstanceOf(DtoCollection::class, $user->test4);
        $this->assertTrue($user->test4->isNotEmpty());
        $this->assertInstanceOf(TestDto::class, $user->test4->first());
        $this->assertTrue($user->test4->first()->number === 2);
        $this->assertTrue($user->test4->first()->name === 'John Doe');
        $this->assertTrue($user->test4->first()->email === 'test@gmail.com');
        $user->test4->first()->name = 'Changed Name';
        $refUser = new \ReflectionClass($user);
        $method = $refUser->getMethod('mergeAttributesFromCachedCasts');
        $method->setAccessible(true);
        $method->invoke($user);
        $this->assertTrue(str_contains($user->syncOriginal()->getRawOriginal('test4'), '"number":13'));
        $this->assertTrue(str_contains($user->syncOriginal()->getRawOriginal('test4'), 'Changed Name'));
    }

    public function test_iterable_array_model_casting(): void
    {
        $user = new User5Model();

        $user->setRawAttributes([
            'test5' => json_encode([1,2,'John Doe',3,4])
        ]);

        $this->assertInstanceOf(DtoCollection::class, $user->test5);
        $this->assertTrue($user->test5->isNotEmpty());
        $this->assertInstanceOf(Test3Dto::class, $user->test5->first());
        $this->assertTrue($user->test5->get(1)->name === 2);
        $this->assertTrue($user->test5->get(2)->name === 'John Doe');

        $refUser = new \ReflectionClass($user);
        $method = $refUser->getMethod('mergeAttributesFromCachedCasts');
        $method->setAccessible(true);
        $method->invoke($user);
        $this->assertTrue($user->syncOriginal()->getRawOriginal('test5') === '[1,2,{"name":"John Doe"},3,4]');
    }
}

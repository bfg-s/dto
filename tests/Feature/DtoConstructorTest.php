<?php

namespace Bfg\Dto\Tests\Feature;

use Bfg\Dto\Collections\DtoCollection;
use Bfg\Dto\Tests\CommentDto;
use Bfg\Dto\Tests\Test2Dto;
use Bfg\Dto\Tests\TestDto;
use Bfg\Dto\Tests\ToDoDto;
use Tests\TestCase;

include_once __DIR__.'/../TestDto.php';
include_once __DIR__.'/../Test2Dto.php';
include_once __DIR__.'/../ToDoDto.php';
include_once __DIR__.'/../CommentDto.php';

class DtoConstructorTest extends TestCase
{
    public function test_from_cache()
    {
        TestDto::fromArray([
            'name' => 'John Doe',
            'email' => 'test@gmail.com',
            'test' => [
                'name' => 'John Doe',
                'email' => 'test2@gmail.com',
            ],
        ])->cache();

        TestDto::clearEvents();
        TestDto::clearGlobalEvents();

        $dto = TestDto::fromCache();

        $this->assertTrue($dto->name === 'John Doe');
        $this->assertTrue($dto->email === 'test@gmail.com');
        $this->assertTrue($dto->test instanceof Test2Dto);
        $this->assertTrue($dto->test->name === 'John Doe');
        $this->assertTrue($dto->test->email === 'test2@gmail.com');
        $this->assertTrue($dto->tests === []);
    }

    public function test_from_get()
    {
        $dto = ToDoDto::fromGet('https://jsonplaceholder.typicode.com/todos/1');

        $this->assertTrue($dto instanceof ToDoDto);
        $this->assertTrue($dto->userId === 1);
        $this->assertTrue($dto->id === 1);
        $this->assertTrue($dto->title === 'delectus aut autem');
        $this->assertTrue($dto->completed === false);

        $dto = CommentDto::fromGet('https://jsonplaceholder.typicode.com/posts/1/comments');

        $this->assertTrue($dto instanceof DtoCollection);
        $this->assertTrue($dto->count() === 5);
        $this->assertTrue($dto->first() instanceof CommentDto);
    }
}

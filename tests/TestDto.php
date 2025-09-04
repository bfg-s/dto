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

    protected static array $dtoCast = [
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

    public function toDatabase(): string
    {
        return json_encode([
            'number' => $this->number + 11,
            'name' => $this->name,
            'email' => $this->email,
            'test' => $this->test->toArray(),
            'collect' => $this->collect->toArray(),
            'tests' => $this->tests instanceof DtoCollection ? $this->tests->toArray() : $this->tests,
        ]);
    }

    public static function fromDatabase(string $data): array
    {
        $data = json_decode($data, true);

        if (is_assoc($data)) {
            $data['number'] = ((int) $data['number']) + 1;

            return $data;
        }

        $data[0]['number'] = ((int) $data[0]['number']) + 1;

        return $data;
    }
}

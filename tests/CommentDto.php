<?php

namespace Bfg\Dto\Tests;

use Bfg\Dto\Dto;

class CommentDto extends Dto
{
    public function __construct(
        public int $postId,
        public int $id,
        public string $name,
        public string $email,
        public string $body,
    ) {
    }
}

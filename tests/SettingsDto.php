<?php

namespace Bfg\Dto\Tests;

use Bfg\Dto\Attributes\DtoMapApi;
use Bfg\Dto\Attributes\DtoMutateFrom;
use Bfg\Dto\Attributes\DtoMutateTo;
use Bfg\Dto\Dto;

class SettingsDto extends Dto
{
    public function __construct(
        #[
            DtoMutateFrom('mutateBoolean'),
            DtoMutateTo('mutateBooleanString'),
            DtoMapApi
        ]
        public bool $receiveNotifications,
    ) {}

    public static function mutateBoolean(mixed $value): bool
    {
        return is_string($value)
            ? trim(strtolower($value)) === 'true'
            : (bool) $value;
    }

    public static function mutateBooleanString(bool $value): string
    {
        return ucfirst($value ? 'true' : 'false');
    }
}

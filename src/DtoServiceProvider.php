<?php

namespace Bfg\Dto;

use Bfg\Dto\Console\MakeDtoCastCommand;
use Bfg\Dto\Console\MakeDtoCommand;
use Bfg\Dto\Console\MakeDtoDocsCommans;
use Illuminate\Support\ServiceProvider;

class DtoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //Register generate command
        $this->commands([
            MakeDtoCommand::class,
            MakeDtoCastCommand::class,
            MakeDtoDocsCommans::class,
        ]);

    }

    public function boot(): void
    {
        //        $this->app->new(DtoContract::class, function ($object) {
        //
        //            return $object::fromRequest(request());
        //        });
    }
}

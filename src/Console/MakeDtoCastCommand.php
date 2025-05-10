<?php

namespace Bfg\Dto\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class MakeDtoCastCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:dto-cast';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a new cast for DTO.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Dto cast';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/cast.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name): string
    {
        $dto = class_basename(Str::ucfirst($name));

        $namespace = $this->getNamespace(
            Str::replaceFirst($this->rootNamespace(), 'App\\Casts\\Dto\\', $this->qualifyClass($this->getNameInput()))
        );

        $replace = [
            '{{ castNamespace }}' => $namespace,
            '{{ cast }}' => $dto,
            '{{cast}}' => $dto,
        ];

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name): string
    {
        $name = (string) Str::of($name)->replaceFirst($this->rootNamespace(), '')->finish('Cast');

        return app_path('Casts/Dto/'.str_replace('\\', '/', $name).'.php');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [

        ];
    }
}

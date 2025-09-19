<?php

namespace Bfg\Dto\Console;

use Bfg\Dto\Collections\DtoCollection;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

class MakeDtoCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:dto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a new DTO class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Dto';

    /**
     * @var array|string[]
     */
    protected array $simplePhpTypes = [
        'int', 'integer', 'float', 'double', 'string', 'bool', 'boolean', 'array', 'object', 'mixed',
        'null', 'callable', 'iterable', 'resource'
    ];

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/dto.stub');
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
        $dto = class_basename(Str::studly($name));

        $namespace = $this->getNamespace(
            Str::replaceFirst($this->rootNamespace(), 'App\\Dto\\', $this->qualifyClass($this->getNameInput()))
        );
        $properties = $this->makeProperties();
        $replace = [
            '{{ dtoNamespace }}' => $namespace,
            '{{ dto }}' => $dto,
            '{{dto}}' => $dto,
            '{{ uses }}' => $properties['uses'],
            '{{ fields }}' => $properties['properties'],
        ];

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }

    /**
     * @return array{uses: string, properties: string}
     */
    protected function makeProperties(): array
    {
        $parameters = [];
        $uses = [];

        foreach ($this->argument('fields') as $field) {
            $field = explode(' ', $field);
            $type = count($field) > 1 ? $field[0] : 'mixed';
            $name = count($field) > 1 ? $field[1] : $field[0];
            $type = explode('|', $type);
            foreach ($type as $typeKey => $unionType) {
                $unionType = trim($unionType);
                if (in_array($unionType, $this->simplePhpTypes)) {
                    continue;
                }
                if (! isset($uses[$unionType])) {
                    $unionTypeName = class_basename($unionType);
                    if ($unionTypeName === 'Collection' || $unionTypeName === 'DtoCollection') {
                        $uses[$unionTypeName] = DtoCollection::class;
                        $type[$typeKey] = 'DtoCollection';
                    } else {
                        $dtoClass = "App\\Dto\\$unionType";
                        if (class_exists($dtoClass)) {
                            $uses[$unionTypeName] = $dtoClass;
                            $type[$typeKey] = $unionTypeName;
                        } else {
                            $modelClass = "App\\Models\\$unionType";
                            if (class_exists($modelClass)) {
                                $uses[$unionTypeName] = $modelClass;
                                $type[$typeKey] = $unionTypeName;
                            } else {
                                $appClass = "App\\$unionType";
                                if (class_exists($appClass)) {
                                    $uses[$unionTypeName] = $appClass;
                                    $type[$typeKey] = $unionTypeName;
                                } else {
                                    $appClass = $unionType;
                                    if (class_exists($appClass)) {
                                        $uses[$unionTypeName] = $appClass;
                                        $type[$typeKey] = $unionTypeName;
                                    } else {
                                        // If the class does not exist, we keep the original type
                                        $type[$typeKey] = $unionType;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $type = implode('|', $type);
            $parameters[] = "public $type \$$name,";
        }

        return [
            'properties' => $parameters ? (str_repeat(' ', 8)
                . implode(PHP_EOL . str_repeat(' ', 8), $parameters)) : '',
            'uses' => $uses ? "\nuse " . implode(';' . PHP_EOL . 'use ', array_values($uses)) . ";" : ''
        ];
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name): string
    {
        $name = (string) Str::of($name)->replaceFirst($this->rootNamespace(), '');

        return app_path('Dto/'.str_replace('\\', '/', $name).'.php');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the '.strtolower($this->type)],
            ['fields', InputArgument::IS_ARRAY, 'The fields of the '.strtolower($this->type)],
        ];
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

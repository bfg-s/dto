<?php

namespace Bfg\Dto\Console;

use Bfg\Dto\Dto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class MakeDtoDocsCommans extends Command
{
    protected $signature = "make:dto-docs";

    protected $description = "Make DTO docs";

    public function handle(): void
    {
        $path = app_path('Dto');

        if (!is_dir($path)) {
            $this->error('Directory not found: ' . $path);
            return;
        }

        $files = File::allFiles($path);

        $classes = collect($files)->map(function ($file) {
            return $this->getClassNameFromFile($file);
        })->filter()->values()->map(function ($class) {
            $this->updateClassDocBlockWithReflection($class['class'], $class['doc'], $class['old_doc']);
            return $class['class'];
        })->count();

        if (! $classes) {

            $this->info('No DTO classes for processing found.');
        } else {

            $this->info('Complete! ' . $classes . ' DTO classes have been processed.');
        }
    }

    protected function getClassNameFromFile(SplFileInfo $file): ?array
    {
        $class = "App\\Dto"
            . str_replace([app_path('Dto'), '/', '.php'], ['', '\\', ''], $file->getPathname());

        if (! class_exists($class)) {

            return null;
        }
        $ref = new \ReflectionClass($class);
        $doc = ($ref)->getDocComment() ?: "";
        $extendProperty = $ref->getProperty('extends');
        $extendProperty->setAccessible(true);
        $extends = $extendProperty->getValue();
        if (method_exists($class, 'getConstructorParameters')) {
            $parameters = $class::getConstructorParameters();
        } else {
            $parameters = [];
        }

        $variables = [];

        /** @var \ReflectionParameter $parameter */
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();
            $types = [];
            if ($type instanceof \ReflectionUnionType) {
                foreach ($type->getTypes() as $unionType) {
                    $types[] = $unionType->getName();
                }
            } else {
                $types[] = $type->getName();
            }
            $variables['lazy' . ucfirst(Str::camel($name))] = implode('|', array_map(function ($val) {
                return class_exists($val) || enum_exists($val) ? "\\" . $val : $val;
            }, $types));
        }

        foreach ($extends as $key => $val) {
            if (is_array($val)) {
                $variables[$key] = implode('|', array_map(function ($val) {
                    return class_exists($val) || enum_exists($val) ? "\\" . $val : $val;
                }, $val));
            } else {
                $variables[$key] = class_exists($val) || enum_exists($val) ? "\\" . $val : $val;
            }

            $variables['lazy'. ucfirst(Str::camel($key))] = $variables[$key];
        }

        $computed = collect($ref->getMethods())->filter(function (\ReflectionMethod $method) {
            return !in_array($method->getName(), [
                    '__construct', '__destruct', '__get', '__set', '__isset', '__unset', '__sleep', '__wakeup',
                    '__serialize', '__unserialize', '__clone', '__toString', '__invoke', '__set_state', '__clone',
                    '__debugInfo', '__serialize', '__unserialize', '__sleep', '__wakeup'
                ]) && ! method_exists(Dto::class, $method->getName())
                && ! str_starts_with($method->getName(), 'fromArray')
                && ! str_starts_with($method->getName(), 'toArray')
                && ! str_starts_with($method->getName(), 'default')
                && ! str_starts_with($method->getName(), 'source')
                && ! str_starts_with($method->getName(), 'with')
                && ! str_starts_with($method->getName(), 'lazy');
        })->values();

        foreach ($computed as $method) {
            $variables[$method->getName()] = 'mixed';
            $variables['lazy'. ucfirst(Str::camel($method->getName()))] = 'mixed';
        }

        if (! $variables) {
            return null;
        }

        return [
            'class' => $class,
            'old_doc' => $doc,
            'doc' => $this->updateDocBlock($doc, $variables),
        ];
    }

    protected function updateClassDocBlockWithReflection(string $className, string $docBlock, string $oldDoc): void
    {
        $reflection = new \ReflectionClass($className);
        $fileName = $reflection->getFileName();
        $code = file_get_contents($fileName);

        if ($oldDoc) {
            $code = str_replace($oldDoc, $docBlock, $code);
        } else {
            $code = preg_replace('/(class\s+' . $reflection->getShortName() . '.*?{)/s', "{$docBlock}\n$0", $code);
        }

        file_put_contents($fileName, $code);
    }

    /**
     * @param  string  $code
     * @param  array  $properties
     * @return string
     */
    protected function updateDocBlock(string $code, array $properties): string|array
    {
        $lines = explode("\n", $code);
        if (! count($lines)) {
            $lines = ['/**', ' */'];
        } else {
            if (! isset($lines[0]) || trim($lines[0]) !== '/**') {
                $lines[0] = '/**';
            }
            $endIndex = count($lines) - 1;
            $endIndex = $endIndex === 0 ? 1 : $endIndex;
            if (! isset($lines[$endIndex]) || trim($lines[$endIndex]) !== ' */') {
                $lines[$endIndex] = ' */';
            }
        }

        $start = $lines[0];
        $end = $lines[count($lines) - 1];
        $lines = array_slice($lines, 1, count($lines) - 2);

        foreach ($properties as $property => $type) {

            if (class_exists($type)) {
                $type = '\\' . $type;
            }

            $edited = collect($lines)->filter(fn (string $line) => str_contains($line, "\${$property} "))->each(function (string $line, int $key) use (&$lines, $property, $type) {
                $lines[$key] = " * @property {$type} \${$property}";
            })->count();

            if (! $edited && ! str_contains($code, "\${$property}")) {

                $lines[] = " * @property {$type} \${$property}";
            }
        }

        return $start . "\n" . implode("\n", $lines) . "\n" . $end;
    }
}

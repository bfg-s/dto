<?php

declare(strict_types=1);

namespace Bfg\Dto\Traits;

use Bfg\Dto\Exceptions\DtoEventNotFoundException;

trait DtoEventsTrait
{
    public const SET_CURRENT_DATA = '__data__';

    /**
     * @return void
     */
    public static function clearEvents(): void
    {
        static::$__events[static::class] = [];
    }

    /**
     * @return void
     */
    public static function clearGlobalEvents(): void
    {
        static::$__events['global'] = [];
    }
    /**
     * @param  string|array  $event
     * @param  callable  $callback
     * @param  bool  $global
     * @return void
     */
    public static function on(string|array $event, callable $callback, bool $global = false): void
    {
        $parameters = [];

        if (is_string($event) && str_contains($event, ':')) {
            $event = explode(':', $event);
        }

        if (is_array($event)) {
            $eventWithParameters = array_values($event);
            unset($eventWithParameters[0]);
            $parameters = array_values($eventWithParameters);
            static::checkEventName(array_values($event)[0] ?? 'undefined');
            $event = implode(':', $event);
        } else {
            static::checkEventName($event);
        }

        if ($global) {
            static::$__events['global'][$event][] = [$callback, $parameters];
        } else {
            static::$__events[static::class][$event][] = [$callback, $parameters];
        }
    }

    /**
     * @param  string|array  $event
     * @param  mixed|null  $data
     * @param  mixed  ...$args
     * @return mixed
     */
    protected static function fireEvent(string|array $event, mixed $data = null, mixed ...$args): mixed
    {
        if (is_array($event)) {
            static::checkEventName(array_values($event)[0] ?? 'undefined');
            $event = implode(':', $event);
        } else {
            static::checkEventName($event);
        }
        $callbacks = array_merge(
            static::$__events[static::class][$event] ?? [],
            static::$__events['global'][$event] ?? [],
        );

        if ($callbacks) {

            foreach ($callbacks as $callbackData) {
                [$callback, $parameters] = $callbackData;
                foreach ($args as $key => $arg) {
                    if ($arg === static::SET_CURRENT_DATA) {
                        $args[$key] = $data;
                    }
                }
                $parameters = array_merge($args, $parameters);
                $result = call_user_func($callback, ...$parameters);
                if ($result) {
                    if (is_array($data)) {
                        if (is_array($result)) {
                            $data = array_merge($data, $result);
                        } else {
                            $data[] = $result;
                        }
                    } else {
                        $data = $result;
                    }
                }
            }
        }

        return $data;
    }

    protected static function checkEventName(string $name): void
    {
        if (!in_array($name, static::$__eventsNames)) {

            throw new DtoEventNotFoundException($name);
        }
    }
}

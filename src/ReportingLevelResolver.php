<?php

namespace Mpietrucha\Error;

use Closure;
use Exception;
use Mpietrucha\Support\Caller;
use Mpietrucha\Support\Types;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ReportingLevelResolver
{
    protected static ?Collection $operators = null;

    protected static ?Closure $defaultOperator = null;

    protected const LEVELS = [
        'disable' => 0,
        'all' => E_ALL,
        'warning' => E_WARNING,
        'notice' => E_NOTICE,
        'core_error' => E_CORE_ERROR,
        'core_warning' => E_CORE_WARNING,
        'compile_error' => E_COMPILE_ERROR,
        'compile_warning' => E_COMPILE_WARNING,
        'user_error' => E_USER_ERROR,
        'user_warning' => E_USER_WARNING,
        'user_notice' => E_USER_NOTICE,
        'strict' => E_STRICT,
        'recoverable' => E_RECOVERABLE_ERROR,
        'deprecated' => E_DEPRECATED,
        'user_deprecated' => E_USER_DEPRECATED
    ];

    public static function __callStatic(string $method, array $arguments): int
    {
        [$currentLevel, $defaultLevel] = $arguments + [null, null];

        $level = str($method);

        $builder = self::operators()->filter(fn (Closure $handler, string $operator) => $level->startsWith($operator));

        $handler = Caller::create($builder->first())->add(self::$defaultOperator)->add(function (int $current, int $level) {
            return $current | $level;
        })->get();

        $level = $level->after($builder->keys()->first() ?? '')->snake()->whenEmpty(
            fn (string $level) => Arr::get(self::LEVELS, $builder->keys()->first(), $defaultLevel),
            fn (string $level) => Arr::get(self::LEVELS, $level)
        );

        if (! Types::int($level)) {
            throw new Exception("Method $method not found");
        }

        if (Types::null($currentLevel)) {
            throw new Exception('Invalid level argument');
        }

        return $handler($currentLevel, $level);
    }

    public static function operators(): Collection
    {
        return self::$operators ??= collect([
            'disable' => fn (int $current, int $level) => $level,
            'without' => fn (int $current, int $level) => $current & ~ $level
        ]);
    }

    public static function operator(string $operator, Closure $handler): void
    {
        self::operators()->put($operator, $handler);
    }

    public static function setDefaultOperator(Closure $operator): Closure
    {
        self::$defaultOperator = $operator;
    }
}

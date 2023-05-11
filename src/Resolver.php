<?php

namespace Mpietrucha\Error;

use Closure;
use Mpietrucha\Support\Concerns\HasFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Resolver
{
    use HasFactory;

    protected ?string $level = null;

    protected ?string $operator = null;

    protected static ?array $operators = null;

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

    public function __construct(protected string $method)
    {
    }

    public static function operators(): array
    {
        return self::$operators ??= [
            'disable' => fn (int $current, int $level) => $level,
            'without' => fn (int $current, int $level) => $current & ~ $level
        ];
    }

    public static function defaultOperator(): Closure
    {
        return self::$defaultOperator ??= fn (int $current, int $level) => $current | $level;
    }

    public static function setOperators(array $operators): void
    {
        self::$operators = $operators;
    }

    public static function setDefaultOperator(Closure $operator): void
    {
        self::$defaultOperator = $operator;
    }

    public function level(): ?int
    {
        return Arr::get(self::LEVELS, $this->build()->level);
    }

    public function callback(): Closure
    {
        if (! $operator = $this->build()->operator) {
            return self::defaultOperator();
        }

        return Arr::get(self::operators(), $operator);
    }

    protected function build(): self
    {
        if ($this->level) {
            return $this;
        }

        $level = str($this->method);

        $operator = collect(self::operators())->keys()->first(fn (string $operator) => $level->startsWith($operator));

        $this->operator = $operator;

        $this->level = $level->after($this->operator ?? '')->whenEmpty(fn () => $level)->snake();

        return $this;
    }
}

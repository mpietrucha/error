<?php

namespace Mpietrucha\Error;

use Mpietrucha\Support\Macro;
use Illuminate\Support\Collection;
use Mpietrucha\Support\Concerns\HasFactory;

class Error
{
    use HasFactory;

    protected static ?Collection $errors = null;

    protected const DEFAULT_BAG = 'default';

    public function __construct(protected int $level, protected string $error, protected string $file, protected int $line)
    {
    }

    public function __call(string $method, array $arguments): string|int
    {
        return $this->$method;
    }

    public static function all(): Collection
    {
        return self::$errors ??= collect();
    }

    public static function clear(?string $bag = null): Collection
    {
        $errors = self::get($bag);

        self::all()->forget($bag ?? self::DEFAULT_BAG);

        return $errors;
    }

    public static function get(?string $bag = null): Collection
    {
        return self::all()->get($bag ?? self::DEFAULT_BAG, collect());
    }

    public static function add(array $error, ?string $bag = null): void
    {
        Macro::bootstrap();

        $instance = self::create(...$error);

        self::all()->list($bag ?? self::DEFAULT_BAG, $instance);
    }
}

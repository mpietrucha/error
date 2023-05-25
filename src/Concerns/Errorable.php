<?php

namespace Mpietrucha\Error\Concerns;

use Illuminate\Support\Collection;
use Mpietrucha\Error\Repository\Error;
use Mpietrucha\Exception\RuntimeException;

trait Errorable
{
    protected ?string $errorableAs = null;

    protected static ?Collection $errors = null;

    protected static string $defaultErrorableAs = 'default';

    public static function errors(?string $name = null): Collection
    {
        return self::withDefaultErrors()->get($name ?? self::$defaultErrorableAs, collect());
    }

    public static function defaultErrorableAs(string|Closure $name): void
    {
        self::$defaultErrorableAs = value($name, self::$defaultErrorableAs);
    }

    protected static function withDefaultErrors(): Collection
    {
        return self::$errors ??= collect();
    }

    public function errorableAs(string $name): self
    {
        $this->errorableAs = $name;

        return $this;
    }

    protected function createError(): void
    {
        $error = Error::create(...func_get_args());

        self::withDefaultErrors()->list($this->errorableAs ?? self::$defaultErrorableAs, $error);
    }
}

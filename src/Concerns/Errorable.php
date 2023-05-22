<?php

namespace Mpietrucha\Error\Concerns;

use Illuminate\Support\Collection;
use Mpietrucha\Error\Repository\Error;
use Mpietrucha\Exception\RuntimeException;

trait Errorable
{
    protected static ?Collection $errors = null;

    public static function errors(): Collection
    {
        return self::$errors ??= collect();
    }

    protected function createError(): void
    {
        $error = Error::create(...func_get_args());

        self::errors()->push($error);
    }
}

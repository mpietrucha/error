<?php

namespace Mpietrucha\Error\System;

use Closure;
use Mpietrucha\Error\Contracts\SystemInterface;
use Mpietrucha\Error\Contracts\SystemHandlerInterface;

abstract class System implements SystemInterface, SystemHandlerInterface
{
    abstract protected static function setUsing(): Closure;

    abstract protected static function restoreUsing(): Closure;

    public static function set(?Closure $value = null): null|array|Closure
    {
        return static::setUsing()($value);
    }

    public static function get(): ?Closure
    {
        $value = self::set(function () {
            return false;
        });

        self::restore();

        if (! $value) {
            return $value;
        }

        if ($value instanceof Closure) {
            return $value;
        }

        [$handler, $method] = $value;

        return fn () => $handler->$method(...func_get_args());
    }

    public static function restore(): void
    {
        static::restoreUsing()();
    }

    public static function restoreDefault(): void
    {
        while(self::get()) {
            self::restore();
        }
    }

    public static function getThenRestore(): ?Closure
    {
        $value = self::get();

        self::restore();

        return $value;
    }

    public static function getThenRestoreDefault(): ?Closure
    {
        $value = self::get();

        self::restoreDefault();

        return $value;
    }
}

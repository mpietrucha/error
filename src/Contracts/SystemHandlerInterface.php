<?php

namespace Mpietrucha\Error\Contracts;

interface SystemHandlerInterface
{
    public static function restore(): void;

    public static function restoreDefault(): void;

    public static function getThenRestore(): mixed;

    public static function getThenRestoreDefault(): mixed;
}

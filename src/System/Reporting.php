<?php

namespace Mpietrucha\Error\System;

use Mpietrucha\Error\Contracts\SystemInterface;

class Reporting implements SystemInterface
{
    public static function set(?int $value = null): int
    {
        return error_reporting($value);
    }

    public static function get(): int
    {
        return self::set();
    }
}

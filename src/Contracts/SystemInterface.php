<?php

namespace Mpietrucha\Error\Contracts;

interface SystemInterface
{
    public static function set(): mixed;

    public static function get(): mixed;
}

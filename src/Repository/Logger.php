<?php

namespace Mpietrucha\Error\Repository;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Collection;

class Logger
{
    public static function get(): LoggerInterface
    {
        return collect([self::global(), self::null()])->filter()->first();
    }

    public static function global(): ?LoggerInterface
    {
        if (! function_exists('logger')) {
            return null;
        }

        $logger = logger();

        if (! $logger instanceof LoggerInterface) {
            return null;
        }

        return $logger;
    }

    public static function null(): LoggerInterface
    {
        return new NullLogger;
    }
}

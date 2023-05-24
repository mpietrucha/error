<?php

namespace Mpietrucha\Error\Concerns;

use Closure;
use Mpietrucha\Support\Reflector;

trait Creators
{
    public static function build(?Closure $handler = null, ?self $instance = null)
    {
        $shouldPassInstance = Reflector::closure($handler)->getNumberOfParameters() === 1;

        if (! $shouldPassInstance) {
            $handler();

            return;
        }

        $handler($instance ??= self::create());

        $instance->register();
    }
}

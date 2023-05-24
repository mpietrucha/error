<?php

namespace Mpietrucha\Error\Concerns;

use Closure;
use Mpietrucha\Support\Reflector;

trait Creators
{
    public static function build(?Closure $handler = null, ?self $instance = null): ?self
    {
        if (! $handler) {
            return $instance ?? self::create();
        }

        $shouldPassInstance = Reflector::closure($handler)?->getNumberOfParameters() === 1;

        if (! $shouldPassInstance) {
            $handler();

            return null;
        }

        $handler($instance ??= self::create());

        return $instance->register();
    }
}

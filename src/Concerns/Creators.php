<?php

namespace Mpietrucha\Error\Concerns;

use Closure;
use Mpietrucha\Support\Reflector;

trait Creators
{
    public static function build(?Closure $handler = null, ?self $instance = null): ?self
    {
        $shouldPassInstance = Reflector::closure($handler)?->getNumberOfParameters() === 1;

        if (! $handler || $shouldPassInstance) {
            $instance ??= self::create();
        }

        if (! $handler) {
            return $instance->register();
        }

        if (! $shouldPassInstance) {
            $handler();

            return null;
        }

        $handler($instance);

        return $instance->register();
    }
}

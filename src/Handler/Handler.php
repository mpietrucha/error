<?php

namespace Mpietrucha\Error\Handler;

use Closure;
use Mpietrucha\Support\Types;
use Mpietrucha\Error\Contracts\BuilderInterface;
use Mpietrucha\Error\Contracts\ExceptionHandlerInterface;

abstract class Handler implements ExceptionHandlerInterface
{
    abstract protected function run(BuilderInterface $builder): void;

    public function handle(BuilderInterface $builder, Closure $next): BuilderInterface
    {
        $this->run($builder);

        return $next($builder);
    }
}

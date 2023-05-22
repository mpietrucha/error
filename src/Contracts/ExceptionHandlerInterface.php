<?php

namespace Mpietrucha\Error\Contracts;

use Closure;

interface ExceptionHandlerInterface
{
    public function handle(BuilderInterface $builder, Closure $next): BuilderInterface;
}

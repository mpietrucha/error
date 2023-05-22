<?php

namespace Mpietrucha\Error\Handler;

use Closure;
use Mpietrucha\Error\Contracts\BuilderInterface;

class ClosureHandler extends Handler
{
    public function __construct(protected Closure $handler)
    {
    }

    protected function run(BuilderInterface $builder): void
    {
        value($this->handler, $builder);
    }
}

<?php

namespace Mpietrucha\Error\Concerns;

use Closure;
use Mpietrucha\Error\Handler;

trait InteractsWithErrorHandler
{
    protected ?Handler $handler = null;

    public function withErrorHandler(?Closure $builder = null): self
    {
        $this->handler = Handler::build($builder);

        return $this;
    }
}

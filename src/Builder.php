<?php

namespace Mpietrucha\Error;

use Closure;
use Throwable;
use Mpietrucha\Error\Contracts\BuilderInterface;

class Builder implements BuilderInterface
{
    protected Closure $handler;

    protected ?Throwable $current = null;

    protected ?Throwable $original = null;

    protected bool $shouldRunHandler = true;

    public function setException(Throwable $exception): self
    {
        $this->current = $exception;

        if (! $this->original) {
            $this->original = $exception;
        }

        return $this;
    }

    public function getException(): Throwable
    {
        return $this->original;
    }

    public function setHandler(Closure $handler): self
    {
        $this->handler = $handler;

        return $this;
    }

    public function getHandler(): Closure
    {
        return $this->handler;
    }

    public function quiet(): self
    {
        $this->shouldRunHandler = false;

        return $this;
    }

    public function shouldRunHandler(): bool
    {
        return $this->shouldRunHandler;
    }

    public function build(): void
    {
        if (! $this->shouldRunHandler()) {
            return;
        }

        value($this->getHandler(), $this->getException());
    }
}

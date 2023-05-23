<?php

namespace Mpietrucha\Error;

use Closure;
use Throwable;
use Psr\Log\LoggerInterface;
use Mpietrucha\Error\Contracts\BuilderInterface;

class Builder implements BuilderInterface
{
    protected Closure $handler;

    protected LoggerInterface $logger;

    protected ?Throwable $exception = null;

    protected bool $shouldRunHandler = true;

    public function setException(Throwable $exception): self
    {
        $this->exception = $exception;

        return $this;
    }

    public function getException(): Throwable
    {
        return $this->exception;
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

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
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

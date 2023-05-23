<?php

namespace Mpietrucha\Error\Contracts;

use Closure;
use Throwable;
use Psr\Log\LoggerInterface;

interface BuilderInterface
{
    public function setException(Throwable $exception): self;

    public function getException(): Throwable;

    public function setHandler(Closure $handler): self;

    public function getHandler(): Closure;

    public function setLogger(LoggerInterface $logger): self;

    public function getLogger(): LoggerInterface;

    public function quiet(): self;

    public function shouldRunHandler(): bool;

    public function build(): void;
}

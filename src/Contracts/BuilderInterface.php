<?php

namespace Mpietrucha\Error\Contracts;

use Closure;
use Throwable;

interface BuilderInterface
{
    public function setException(Throwable $exception): self;

    public function getException(): Throwable;

    public function setHandler(Closure $handler): self;

    public function getHandler(): Closure;

    public function quiet(): self;

    public function shouldRunHandler(): bool;

    public function build(): void;
}

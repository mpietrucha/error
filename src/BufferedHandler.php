<?php

namespace Mpietrucha\Error;

use Closure;
use Mpietrucha\Support\Types;
use Mpietrucha\Support\Concerns\HasFactory;

class BufferedHandler
{
    use HasFactory;

    protected ?Closure $next;

    protected ?string $bag = null;

    protected bool $propagate = false;

    protected bool $shouldRestore = false;

    public function __construct(protected int $level = E_ALL)
    {
        $this->next = $this->next();

        $this->register($level);
    }

    public function propagate(bool $propagate = true): self
    {
        $this->propagate = $propagate;

        return $this;
    }

    public function bag(?string $bag = null): self
    {
        $this->bag = $bag;

        return $this;
    }

    public function register(int $level): void
    {
        $this->restore();

        $this->level = ReportingLevelResolver::without($this->level, $level);

        set_error_handler($this->callback(...));

        $this->shouldRestore = true;
    }

    public function restore(): void
    {
        if (! $this->shouldRestore) {
            return;
        }

        restore_error_handler();

        $this->shouldRestore = false;
    }

    protected function callback(): bool
    {
        $arguments = func_get_args();

        if ($this->shouldHandleThisError(...$arguments)) {
            Error::add($arguments, $this->bag);
        }

        if ($this->propagate) {
            return value($this->next, ...$arguments);
        }

        return true;
    }

    protected function shouldHandleThisError(int $level): bool
    {
        return $level < $this->level;
    }

    protected function next(): Closure
    {
        $handler = set_error_handler(function () {
            return false;
        });

        restore_error_handler();

        if (! $handler) {
            return fn () => false;
        }

        if (Types::array($handler)) {
            return function () use ($handler) {
                [$handler, $method] = $handler;

                return $handler->$method(...func_get_args());
            };
        }

        return $handler;
    }
}

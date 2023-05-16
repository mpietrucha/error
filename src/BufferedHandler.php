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

    protected bool $propagateDefault = false;

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

    public function propagateDefault(bool $propagate = true): self
    {
        $this->propagateDefault = $propagate;

        return $this;
    }

    public function bag(?string $bag = null): self
    {
        $this->bag = $bag;

        return $this;
    }

    public function level(int $level, bool $shouldSetValue = false): self
    {
        $current = ReportingLevelResolver::without(E_ALL, $level);

        if ($shouldSetValue || $level === 0 || $level === E_ALL) {
            $this->level = $current;

            return $this;
        }

        return $this->level($current, true);
    }

    public function register(int $level): void
    {
        $this->level($level)->restore();

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
            Error::create(...$arguments);
        }

        if ($this->propagate) {
            return value($this->next, ...$arguments) ?? $this->propagateDefault;
        }

        return $this->propagateDefault;
    }

    protected function shouldHandleThisError(int $level): bool
    {
        return $this->level > $level;
    }

    protected function next(): Closure
    {
        $handler = set_error_handler(function () {
            return false;
        });

        restore_error_handler();

        if (! $handler) {
            return fn () => $this->propagateDefault;
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

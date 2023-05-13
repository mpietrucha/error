<?php

namespace Mpietrucha\Error;

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
        $this->next = set_error_handler(function () {});

        restore_error_handler();

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

        set_error_handler($this->callback(...), ReportingLevelResolver::without($this->level, $level));

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
        Error::add($arguments = func_get_args(), $this->bag);

        if ($this->propagate) {
            return value($this->next, ...$arguments) ?? false;
        }

        return true;
    }
}

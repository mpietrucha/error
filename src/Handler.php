<?php

namespace Mpietrucha\Error;

use Mpietrucha\Support\Concerns\HasFactory;

class Handler
{
    use HasFactory;

    protected ?string $bag = null;

    protected bool $bypass = false;

    protected bool $shouldRestore = false;

    public function __construct(protected int $level = E_ALL)
    {
        $this->register($level);
    }

    public function bypass(): self
    {
        $this->bypass = true;

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

        set_error_handler(function () {
            Error::add(func_get_args(), $this->bag);

            return ! $this->bypass;
        }, Resolver::create('without')->callback()($this->level, $level));

        $this->shouldRestore = true;
    }

    public function restore(): void
    {
        if (! $this->shouldRestore) {
            return;
        }

        restore_error_handler();
    }
}

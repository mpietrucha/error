<?php

namespace Mpietrucha\Error;

use Mpietrucha\Support\Concerns\HasFactory;

class BufferedHandler
{
    use HasFactory;

    protected ?string $bag = null;

    protected bool $propagate = false;

    protected bool $shouldRestore = false;

    protected const LEVEL_MODE = 'without';

    public function __construct(protected int $level = E_ALL)
    {
        $this->register($level);
    }

    public function propagate(): self
    {
        $this->propagate = true;

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

        set_error_handler($this->callback(...), $this->level($level));

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
        Error::add(func_get_args(), $this->bag);

        return ! $this->propagate;
    }

    protected function level(int $level): int
    {
        return ReporingLevelResolver::create(self::LEVEL_MODE)->callback()($this->level, $level);
    }
}

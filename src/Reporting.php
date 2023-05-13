<?php

namespace Mpietrucha\Error;

use Closure;
use Mpietrucha\Support\Macro;
use Illuminate\Support\Collection;
use Mpietrucha\Support\Concerns\HasFactory;

class Reporting
{
    use HasFactory;

    protected int $origin;

    protected int $builder;

    protected ?BufferedHandler $handler = null;

    public function __construct(protected ?string $version = null)
    {
        $this->handler = BufferedHandler::create(
            $this->origin = $this->builder = error_reporting()
        );

        $this->propagateError();

        Macro::bootstrap();
    }

    public function __destruct()
    {
        $this->commit();
    }

    public function __call(string $method, array $arguments): self
    {
        $level = ReportingLevelResolver::$method($this->builder);

        if ($this->shouldRunInThisPHPVersion()) {
            $this->builder = $level;
        }

        return $this;
    }

    public function withoutErrorHandler(): self
    {
        $this->handler?->restore();

        $this->handler = null;

        return $this;
    }

    public function propagateError(bool $propagate = true): self
    {
        $this->handler?->propagate($propagate);

        return $this;
    }

    public function propagateDefaultError(bool $propagate = true): self
    {
        $this->handler?->propagateDefault($propagate);

        return $this;
    }

    public function withErrorBag(?string $bag = null): self
    {
        $this->handler?->bag($bag);

        return $this;
    }

    public function while(Closure $callback): mixed
    {
        $this->commit();

        $returnValue = $callback();

        $this->restore();

        return $returnValue;
    }

    public function commit(): void
    {
        $this->set($this->builder);

        $this->handler?->register($this->builder);

        $this->propagateError();
    }

    protected function set(int $level): void
    {
        error_reporting($level);
    }

    protected function restore(): void
    {
        $this->builder = $this->origin;
    }

    protected function shouldRunInThisPHPVersion(): bool
    {
        if (! $this->version) {
            return true;
        }

        return str()->php()->is($this->version);
    }
}

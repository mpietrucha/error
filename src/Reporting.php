<?php

namespace Mpietrucha\Error;

use Closure;
use Exception;
use Mpietrucha\Support\Macro;
use Mpietrucha\Support\Types;
use Illuminate\Support\Collection;
use Mpietrucha\Support\Concerns\HasFactory;

class Reporting
{
    use HasFactory;

    protected int $origin;

    protected int $builder;

    protected BufferedHandler $handler;

    protected const WHILE_BAG = 'while';

    public function __construct(protected ?string $version = null)
    {
        $this->handler = BufferedHandler::create(
            $this->origin = $this->builder = error_reporting()
        );

        Macro::bootstrap();
    }

    public function __destruct()
    {
        $this->commit();
    }

    public function __call(string $method, array $arguments): self
    {
        $resolver = ReporingLevelResolver::create($method);

        if (Types::null($level = $resolver->level())) {
            throw new Exception("Method $method not found.");
        }

        if ($this->shouldRunInThisPHPVersion()) {
            $this->builder = $resolver->callback()($this->builder, $level);
        }

        return $this;
    }

    public static function withFreshAllErrors(): void
    {
        self::withFreshErrors(true);
    }

    public static function withFreshErrors(bool $all = false): void
    {
        if ($all) {
            self::allErrors();

            return;
        }

        self::errors();
    }

    public static function errors(): Collection
    {
        return self::allErrors(self::WHILE_BAG);
    }

    public static function allErrors(?string $bag = null): Collection
    {
        return Error::clear($bag);
    }

    public function propagate(): self
    {
        $this->handler->propagate();

        return $this;
    }

    public function while(Closure $callback): mixed
    {
        $this->commit();

        $this->handler->bag(self::WHILE_BAG);

        $returnValue = $callback();

        $this->builder = $this->origin;

        $this->handler->bag()->restore();

        return $returnValue;
    }

    public function commit(): void
    {
        $this->set($this->builder);

        $this->handler->register($this->builder);
    }

    protected function set(int $level): void
    {
        error_reporting($level);
    }

    protected function shouldRunInThisPHPVersion(): bool
    {
        if (! $this->version) {
            return true;
        }

        return str()->php()->is($this->version);
    }
}

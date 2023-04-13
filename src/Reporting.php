<?php

namespace Mpietrucha\Php\Error;

use Closure;
use Exception;
use Illuminate\Support\Stringable;
use Mpietrucha\Support\Concerns\HasFactory;

class Reporting
{
    use HasFactory;

    protected int $origin;

    protected int $builder;

    protected ?Stringable $phpVersion = null;

    public function __construct(protected ?string $version = null)
    {
        $this->origin = $this->builder = error_reporting();
    }

    public function __destruct()
    {
        $this->set($this->builder);
    }

    public function __call(string $method, array $arguments): self
    {
        $resolver = Resolver::create($method);

        if (! $level = $resolver->level()) {
            throw new Exception("Method $method not found.");
        }

        if ($this->shouldRunInThisPHPVersion()) {
            $callback = $resolver->callback();

            $this->builder = $callback($this->builder, $level);
        }

        return $this;
    }

    public function while(Closure $callback): mixed
    {
        $this->set($this->builder);

        $returnValue = $callback();

        $this->builder = $this->origin;

        return $returnValue;
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

        return ($this->phpVersion ??= str(phpversion()))->is($this->version);
    }
}

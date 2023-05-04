<?php

namespace Mpietrucha\Php\Error;

use Closure;
use Exception;
use Mpietrucha\Support\Types;
use Illuminate\Support\Stringable;
use Illuminate\Support\Collection;
use Mpietrucha\Support\Concerns\HasFactory;

class Reporting
{
    use HasFactory;

    protected int $origin;

    protected int $builder;

    protected Handler $handler;

    protected ?Stringable $phpVersion = null;

    protected static ?Collection $errors = null;

    protected const WHILE_BAG = 'while';

    public function __construct(protected ?string $version = null)
    {
        $this->origin = $this->builder = error_reporting();

        $this->handler = Handler::create();
    }

    public function __destruct()
    {
        $this->commit();
    }

    public function __call(string $method, array $arguments): self
    {
        $resolver = Resolver::create($method);

        if (Types::null($level = $resolver->level())) {
            throw new Exception("Method $method not found.");
        }

        if ($this->shouldRunInThisPHPVersion()) {
            $this->builder = $resolver->callback()($this->builder, $level);
        }

        return $this;
    }

    public static function errors(): Collection
    {
        return self::$errors ?? collect();
    }

    public function while(Closure $callback): mixed
    {
        $this->commit();

        $this->handler->bag(self::WHILE_BAG);

        $returnValue = $callback();

        $this->builder = $this->origin;

        $this->handler->bag()->restore();

        self::$errors = Error::clear(self::WHILE_BAG);

        return $returnValue;
    }

    public function bypass(): self
    {
        $this->handler->bypass();

        return $this;
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

        return ($this->phpVersion ??= str(phpversion()))->is($this->version);
    }
}

<?php

namespace Mpietrucha\Error;

use Closure;
use Mpietrucha\Error\Concerns\Errorable;
use Mpietrucha\Error\Repository\Error;
use Mpietrucha\Support\Concerns\HasFactory;

class Reporting
{
    use Errorable;

    use HasFactory;

    protected int $level;

    protected ?Closure $error = null;

    public function __construct(protected ?string $version = null)
    {
        $this->level(System\Reporting::get());

        $this->error = System\Error::get();

        System\Error::set($this->handle(...));
    }

    public function __destruct()
    {
        $this->commit();
    }

    public function __call(string $method, array $arguments): self
    {
        if ($this->shouldRunInThisPHPVersion()) {
            $level = Level::$method($this->level);

            $this->level($level);
        }

        return $this;
    }

    public function level(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function while(Closure $callback)
    {
        $level = System\Reporting::get();

        $this->commit();

        $callback();

        $this->level($level)->commit();
    }

    public function commit(): void
    {
        System\Reporting::set($this->level);
    }

    protected function shouldRunInThisPHPVersion(): bool
    {
        if (! $this->version) {
            return true;
        }

        return str()->php()->is($this->version);
    }

    protected function handle(int $level, string $error, string $file, int $line): bool
    {
        $handling = $level & $this->level;

        if ($handling === 0) {
            $this->createError($level, $error, $file, $line);

            return true;
        }

        if ($this->error) {
            return value($this->error, $level, $error, $file, $line);
        }

        return false;
    }
}

<?php

namespace Mpietrucha\Error;

use Closure;
use Mpietrucha\Error\Level;
use Mpietrucha\Support\Rescue;
use Mpietrucha\Error\Repository;
use Mpietrucha\Error\Concerns\Errorable;
use Mpietrucha\Error\Concerns\Loggerable;
use Mpietrucha\Support\Concerns\HasFactory;
use Mpietrucha\Repository\Concerns\Repositoryable;
use Mpietrucha\Repository\Contracts\RepositoryInterface;

class Reporting
{
    use Errorable;

    use HasFactory;

    use Loggerable;

    use Repositoryable {
        __call as repositoryCall;
    }

    protected ?int $level = null;

    protected ?Closure $error = null;

    protected ?Handler $handler = null;

    public function __construct(protected ?string $version = null)
    {
        $this->withRepository(new Repository\Handler)->repositoryMethod('usingLogger');

        if ($this->currentRepositoryIsStatic()) {
            return;
        }

        $this->level(System\Reporting::get());

        $this->error = System\Error::get();

        System\Error::set($this->handle(...));
    }

    public function __destruct()
    {
        $this->register();
    }

    public function __call(string $method, array $arguments): self
    {
        Rescue::create(fn () => $this->repositoryCall($method, $arguments))
            ->fail(fn () => $this->level(Level::$method($this->level)))
            ->call();

        return $this;
    }

    public function withErrorHandler(): self
    {
        $this->handler = Handler::create()->register();

        return $this;
    }

    public function level(int $level): self
    {
        if ($this->shouldRunInThisPHPVersion()) {
            $this->level = $level;
        }

        return $this;
    }

    public function while(Closure $callback): mixed
    {
        $level = System\Reporting::get();

        $this->register();

        $response = $callback();

        $this->level($level)->register();

        $this->handler?->restore();

        return $response;
    }

    public function register(): void
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
            $this->log($level, $error)->createError($level, $error, $file, $line);

            return true;
        }

        if ($this->error) {
            return value($this->error, $level, $error, $file, $line);
        }

        return false;
    }
}

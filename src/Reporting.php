<?php

namespace Mpietrucha\Error;

use Error;
use Closure;
use Throwable;
use ErrorException;
use Mpietrucha\Support\Rescue;
use Mpietrucha\Error\Concerns\Errorable;
use Mpietrucha\Error\Concerns\Loggerable;
use Mpietrucha\Support\Concerns\HasFactory;
use Mpietrucha\Repository\Concerns\Repositoryable;
use Mpietrucha\Error\Concerns\InteractsWithErrorHandler;

class Reporting
{
    use Errorable;

    use HasFactory;

    use Loggerable;

    use Repositoryable {
        __call as repositoryCall;
    }

    use InteractsWithErrorHandler;

    protected ?int $level = null;

    protected bool $catch = false;

    protected ?Closure $error = null;

    protected ?Throwable $exception = null;

    public function __construct(protected ?string $version = null)
    {
        $this->withRepository(new Repository\Handler)->withRepositoryMethod('usingLogger');

        if ($this->getRepository()->static()) {
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
            ->fail(fn () => $this->level(
                Level::$method($this->level)
            ))->call();

        return $this;
    }

    public function level(int $level): self
    {
        if ($this->shouldRunInThisPHPVersion()) {
            $this->level = $level;
        }

        return $this;
    }

    public function catch(bool $mode = true): self
    {
        $this->catch = $mode;

        return $this;
    }

    public function while(Closure $callback): mixed
    {
        $level = System\Reporting::get();

        $this->register();

        $response = Rescue::create($callback)->fail($this->catchException(...))->call();

        $this->level($level)->register();

        throw_if($this->exception, $this->exception);

        if (! $this->handler?->restore()) {
            System\Error::restore();
        }

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

    protected function catchException(Throwable $exception): void
    {
        if (! $code = $this->exceptionToCode($this->exception = $exception)) {
            return;
        }

        if (! $this->handle(...$this->exceptionToError($exception, $code))) {
            return;
        }

        $this->exception = null;
    }

    protected function exceptionToError(Throwable $exception, ?int $code = null): array
    {
        return [$code ?? $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine()];
    }

    protected function exceptionToCode(Throwable $exception, int $default = E_RECOVERABLE_ERROR): ?int
    {
        if ($exception instanceof Error) {
            return $default;
        }

        if ($exception instanceof ErrorException) {
            return $exception->getSeverity();
        }

        if ($this->catch) {
            return $default;
        }

        return null;
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

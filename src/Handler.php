<?php

namespace Mpietrucha\Error;

use Closure;
use Throwable;
use Whoops\Run;
use Whoops\RunInterface;
use Psr\Log\LoggerInterface;
use Mpietrucha\Support\Macro;
use Mpietrucha\Error\Enum\Type;
use Mpietrucha\Support\Pipeline;
use Illuminate\Support\Collection;
use Whoops\Handler\HandlerInterface;
use Mpietrucha\Support\Concerns\HasFactory;
use Mpietrucha\Error\Contracts\BuilderInterface;
use Mpietrucha\Repository\Concerns\Repositoryable;
use Mpietrucha\Repository\Contracts\RepositoryInterface;

class Handler
{
    use HasFactory;

    use Repositoryable;

    protected ?Closure $error = null;

    protected ?Closure $exception = null;

    public function __construct()
    {
        $this->withRepository(new Repository\Handler);

        Macro::bootstrap();

        if ($this->currentRepositoryIsStatic()) {
            return;
        }

        $this->captureDefault();
    }

    public function handlers(?Closure $handler = null): Collection
    {
        $handlers = $this->repositoryValuesCollection($handler ?? function (RepositoryInterface $repository) {
            return $repository->handlers;
        })->filter->count()->first();

        return $handlers?->get($this->type()->value) ?? collect();
    }

    public function whoopsHandlers(): Collection
    {
        $handlers = $this->handlers(fn (RepositoryInterface $repository) => $repository->whoopsHandlers);

        if ($handlers->count()) {
            return $handlers;
        }

        if ($this->exception && $this->usingCapturedException()) {
            return $this->useCapturedException(false)->usingWhoopsUnsafeHandler($this->exception, $this->type())->whoopsHandlers();
        }

        return $this->usingWhoopsHandler($this->type()->handler(), $this->type())->whoopsHandlers();
    }

    public function register(): self
    {
        $this->restoreWhoops();

        $this->whoopsHandlers()->each(function (Closure|HandlerInterface $handler) {
            $this->whoops()->pushHandler($handler);
        });

        $this->whoops()->register();

        $this->run();

        return $this;
    }

    public function restore(): self
    {
        $this->restoreWhoops();
        $this->restoreDefault();

        return $this;
    }

    protected function whoops(): RunInterface
    {
        return $this->repositoryValue(fn (RepositoryInterface $repository) => $repository->whoops, function () {
            return $this->usingWhoops(new Run);
        });
    }

    protected function captureDefault(): void
    {
        $this->error = System\Error::getThenRestoreDefault();

        $this->exception = System\Exception::getThenRestoreDefault();
    }

    protected function restoreDefault(): void
    {
        System\Error::set($this->error);

        System\Exception::set($this->exception);
    }

    protected function restoreWhoops(): void
    {
        $this->whoops()->unregister()->clearHandlers();
    }

    protected function usingCapturedException(): bool
    {
        return $this->repositoryValuesCollection(function (RepositoryInterface $repository) {
            return $repository->useCapturedException;
        })->filterNulls()->first(default: true);
    }

    protected function type(): Type
    {
        return $this->repositoryValue(fn (RepositoryInterface $repository) => $repository->type, function () {
            return $this->usingType(Type::createFromEnvironment());
        });
    }

    protected function logger(): LoggerInterface
    {
        return $this->repositoryValue(fn (RepositoryInterface $repository) => $repository->logger, function () {
            return $this->usingLogger(Repository\Logger::get());
        });
    }

    protected function builder(): BuilderInterface
    {
        return $this->repositoryValue(fn (RepositoryInterface $repository) => $repository->builder, function () {
            return $this->usingBuilder(new Builder);
        });
    }

    protected function run(): void
    {
        $handler = System\Exception::get();

        System\Exception::set(function (Throwable $exception) use ($handler) {
            $builder = $this->builder()->setException($exception)->setHandler($handler);

            Pipeline::create()
                ->send($builder)
                ->through($this->handlers()->toArray())
                ->thenReturn()
                ->build();
        });
    }
}

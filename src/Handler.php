<?php

namespace Mpietrucha\Error;

use Closure;
use Throwable;
use Whoops\Run;
use Whoops\RunInterface;
use Mpietrucha\Support\Macro;
use Mpietrucha\Error\Enum\Type;
use Mpietrucha\Support\Pipeline;
use Illuminate\Support\Collection;
use Whoops\Handler\HandlerInterface;
use Mpietrucha\Error\Concerns\Loggerable;
use Mpietrucha\Error\Concerns\Creators;
use Mpietrucha\Support\Concerns\HasFactory;
use Mpietrucha\Error\Contracts\BuilderInterface;
use Mpietrucha\Repository\Concerns\Repositoryable;
use Mpietrucha\Repository\Contracts\RepositoryInterface;

class Handler
{
    use Creators;

    use HasFactory;

    use Loggerable;

    use Repositoryable;

    protected ?Closure $error = null;

    protected ?Closure $exception = null;

    public function __construct()
    {
        $this->withRepository(new Repository\Handler);

        Macro::bootstrap();

        if ($this->getRepository()->static()) {
            return;
        }

        $this->captureDefault();
    }

    public function handlers(?Closure $handler = null): Collection
    {
        $handlers = $this->getRepository()->collection($handler ?? function (RepositoryInterface $repository) {
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

        if ($this->exception && $this->capturedException()) {
            return $this->usingCapturedException(false)->usingWhoopsUnsafeHandler($this->exception, $this->type())->whoopsHandlers();
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
        return $this->getRepository()->value(fn (RepositoryInterface $repository) => $repository->whoops, function () {
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

    protected function capturedException(): bool
    {
        return $this->getRepository()->collection(function (RepositoryInterface $repository) {
             return $repository->usingCapturedException;
        })->first() ?? true;
    }

    protected function type(): Type
    {
        return $this->getRepository()->value(fn (RepositoryInterface $repository) => $repository->type, function () {
            return $this->usingType(Type::createFromEnvironment());
        });
    }

    protected function builder(): BuilderInterface
    {
        return $this->getRepository()->value(fn (RepositoryInterface $repository) => $repository->builder, function () {
            return $this->usingBuilder(new Builder);
        });
    }

    protected function run(): void
    {
        $handler = System\Exception::get();

        System\Exception::set(function (Throwable $exception) use ($handler) {
            $this->builder()->setLogger($this->logger())->setException($exception)->setHandler($handler);

            Pipeline::create()->send($this->builder())->through($this->handlers()->toArray())->thenReturn();

            $this->log($this->builder()->getException()->getCode(), $this->builder()->getException()->getMessage());

            $this->builder()->build();
        });
    }
}

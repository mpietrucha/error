<?php

namespace Mpietrucha\Error\Repository;

use Closure;
use Whoops\RunInterface;
use Mpietrucha\Error\System;
use Psr\Log\LoggerInterface;
use Mpietrucha\Support\Types;
use Spatie\Ignition\Ignition;
use Mpietrucha\Support\Macro;
use Mpietrucha\Error\Enum\Type;
use Illuminate\Support\Collection;
use Whoops\Handler\HandlerInterface;
use Mpietrucha\Repository\Repository;
use Mpietrucha\Error\Handler\ClosureHandler;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Whoops\Handler\Handler as WhoopsHandler;
use Mpietrucha\Error\Contracts\BuilderInterface;
use Mpietrucha\Error\Contracts\ExceptionHandlerInterface;

class Handler extends Repository
{
    protected ?Type $type = null;

    protected bool $production = false;

    protected ?RunInterface $whoops = null;

    protected ?LoggerInterface $logger = null;

    protected ?BuilderInterface $builder = null;

    protected ?bool $useCapturedException = null;

    public function __construct(protected Collection $whoopsHandlers = new Collection, protected Collection $handlers = new Collection)
    {
        Macro::bootstrap();
    }

    public function usingWhoops(Closure|RunInterface $whoops): void
    {
        $this->whoops = value($whoops);
    }

    public function usingBuilder(Closure|BuilderInterface $builder): void
    {
        $this->builder = value($builder);
    }

    public function usingLogger(Closure|LoggerInterface $logger): void
    {
        $this->logger = value($logger);
    }

    public function usingType(string|Closure|Type $type): void
    {
        if (Types::string($type)) {
            $this->usingType(Type::from($type));

            return;
        }

        $this->type = value($type);
    }

    public function usingProduction(bool|Closure $production): void
    {
        $this->production = value($production);
    }

    public function usingHandler(string|Closure|HandlerInterface|ExceptionHandlerInterface $handler, ?Type $type = null): void
    {
        if (Types::string($handler)) {
            $this->usingHandler(new $handler, $type);

            return;
        }

        if ($handler instanceof Closure) {
            $this->usingHandler(new ClosureHandler($handler));

            return;
        }

        if ($handler instanceof HandlerInterface) {
            $this->usingWhoopsHandler($handler);

            return;
        }

        Type::collection()->onlyValue($type)->each(function (Type $type) use ($handler) {
            $this->handlers->list($type->value, $handler);
        });
    }

    public function usingWhoopsHandler(Closure|HandlerInterface $handler, Type $type): void
    {
        $this->whoopsHandlers->list($type->value, $handler);
    }

    public function usingWhoopsUnsafeSystemHandler(Closure $handler, Type $type): void
    {
        $handler();

        $this->usingWhoopsUnsafeHandler(System\Exception::getThenRestore(), $type);
    }

    public function usingWhoopsUnsafeHandler(Closure $handler, Type $type): void
    {
        $this->usingWhoopsHandler(function () use ($handler) {
            $handler(...func_get_args());

            return WhoopsHandler::QUIT;
        }, $type);
    }

    public function usingCliHandler(mixed $handler): void
    {
        $this->usingHandler($handler, Type::CLI);
    }

    public function usingWebHandler(mixed $handler): void
    {
        $this->usingHandler($handler, Type::WEB);
    }

    public function usingAjaxHandler(mixed $handler): void
    {
        $this->usingHandler($handler, Type::AJAX);
    }

    public function usingSymfonyErrorHandler(?Type $type = null): void
    {
        Type::collection()->onlyValue($type)->each(function (Type $type) {
            $this->usingWhoopsUnsafeSystemHandler(fn () => ErrorHandler::register(), $type);
        });
    }

    public function usingSymfonyWebErrorHandler(): void
    {
        $this->usingSymfonyErrorHandler(Type::WEB);
    }

    public function usingSymfonyCliErrorHandler(): void
    {
        $this->usingSymfonyErrorHandler(Type::CLI);
    }

    public function usingSymfonyAjaxErrorHandler(): void
    {
        $this->usingSymfonyErrorHandler(Type::AJAX);
    }

    public function usingSpatieIgnition(string $theme = 'dark'): void
    {
        $this->usingWhoopsUnsafeSystemHandler(function () use ($theme) {
            return Ignition::make()->setTheme($theme)->register();
        }, Type::WEB);
    }

    public function useCapturedException(bool $mode = true): void
    {
        $this->useCapturedException = $mode;
    }

    public function useDefaultHandler(): void
    {
        $this->useCapturedException(false);
    }
}

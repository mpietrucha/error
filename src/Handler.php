<?php

namespace Mpietrucha\Error;

use Mpietrucha\Cli\Cli;
use Mpietrucha\Support\Package;
use Illuminate\Support\Collection;
use Mpietrucha\Support\Concerns\HasFactory;
use Whoops\Run as Provider;
use Whoops\Handler\HandlerInterface;
use NunoMaduro\Collision\Handler as CliHandler;
use Whoops\Handler\PrettyPageHandler as WebHandler;

class Handler
{
    use HasFactory;

    protected static ?Provider $provider = null;

    protected static ?Collection $handlers = null;

    public function __construct()
    {
        Package::enshure(Cli::class, 'mpietrucha/cli');
    }

    public function __destruct()
    {
        $this->register();
    }

    public static function handlers(): Collection
    {
        return self::$handlers ??= collect();
    }

    public function handler(HandlerInterface $handler, ?string $key = null): HandlerInterface
    {
        if (! self::handlers()->has($handler::class)) {
            self::handlers()->put($key ?? $handler::class, $handler);
        }

        return $handler;
    }

    public static function provider(): Provider
    {
        return self::$provider ??= new Provider;
    }

    public function register(): Provider
    {
        self::provider()->clearHandlers();

        if (! self::handlers()->count()) {
            $this->web();

            $this->cli();
        }

        self::handlers()->each(function (HandlerInterface $handler) {
            self::provider()->pushHandler($handler);
        });

        return self::provider()->register();
    }

    public function web(): ?WebHandler
    {
        if (Cli::inside()) {
            return null;
        }

        return self::handler(new WebHandler, 'web');
    }

    public function cli(): ?CliHandler
    {
        if (! Cli::inside()) {
            return null;
        }

        return self::handler(new CliHandler, 'cli');
    }
}

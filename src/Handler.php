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

    public static function handlers(): Collection
    {
        return self::$handlers ??= collect();
    }

    public function register(): Provider
    {
        $this->web();

        $this->cli();

        return self::$provider;
    }

    public function __destruct()
    {
        if (self::$provider) {
            return;
        }

        self::$provider = new Provider;

        self::handlers()->each(function (HandlerInterface $handler) {
            self::$provider->pushHandler($handler);
        });

        self::$provider->register();
    }

    public function web(): ?WebHandler
    {
        if (Cli::inside() | self::handlers()->has(WebHandler::class)) {
            return self::handlers()->get(WebHandler::class);
        }

        self::handlers()->put(WebHandler::class, $handler = new WebHandler);

        return $handler;
    }

    public function cli(): ?CliHandler
    {
        if (! Cli::inside() || self::handlers()->has(CliHandler::class)) {
            return self::handlers()->get(CliHandler::class);
        }

        self::handlers()->put(CliHandler::class, $handler = new CliHandler);

        return $handler;
    }
}

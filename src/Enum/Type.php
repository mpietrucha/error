<?php

namespace Mpietrucha\Error\Enum;

use Whoops\Util\Misc;
use Mpietrucha\Cli\Cli;
use Mpietrucha\Support\Package;
use Illuminate\Support\Collection;
use Whoops\Handler\HandlerInterface;
use NunoMaduro\Collision\Handler as DefaultCliHandler;
use Whoops\Handler\PrettyPageHandler as DefaultWebHandler;
use Whoops\Handler\JsonResponseHandler as DefaultAjaxHandler;

enum Type: string
{
    case CLI = 'cli';

    case WEB = 'web';

    case AJAX = 'ajax';

    public static function collection(): Collection
    {
        return collect(self::cases());
    }

    public static function createFromEnvironment(): self
    {
        Package::enshure(Cli::class);

        return [[self::WEB, self::AJAX][Misc::isAjaxRequest()], self::CLI][Cli::inside()];
    }

    public function handler(): HandlerInterface
    {
        return match($this) {
            self::CLI => new DefaultCliHandler,
            self::WEB => new DefaultWebHandler,
            self::AJAX => new DefaultAjaxHandler
        };
    }
}

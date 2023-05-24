<?php

namespace Mpietrucha\Error\Concerns;

use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use Mpietrucha\Error\Repository;
use Illuminate\Support\Collection;
use Mpietrucha\Repository\Concerns\Repositoryable;
use Mpietrucha\Repository\Contracts\RepositoryInterface;
use Mpietrucha\Exception\RuntimeException;
use Symfony\Component\ErrorHandler\ErrorHandler;

trait Loggerable
{
    protected ?Collection $loggingLevels = null;

    protected function logger(): LoggerInterface
    {
        throw_unless(class_uses_trait($this, Repositoryable::class), new RuntimeException(
            'Logger can be used only if class uses', [Repositoryable::class], 'trait'
        ));

        return $this->getRepository()->value(fn (RepositoryInterface $repository) => $repository->logger, function () {
            return $this->usingLogger(Repository\Logger::get());
        });
    }

    protected function log(int $level, string $message): self
    {
        $level = $this->getLoggingLevel($level);

        $this->logger()->$level($message);

        return $this;
    }

    protected function getLoggingLevel(int $level, bool $default = false): string
    {
        if ($current = $this->loggingLevels?->get($level)) {
            return $current;
        }

        if ($default) {
            return LogLevel::CRITICAL;
        }

        $symfonyLoggers = invade(new ErrorHandler)->loggers;

        $this->loggingLevels = collect($symfonyLoggers)->map(fn (array $logger) => last($logger));

        return $this->getLoggingLevel($level, true);
    }
}

<?php

namespace Mpietrucha\Error;

use Mpietrucha\Support\File;
use Mpietrucha\Support\Macro;
use Illuminate\Support\Collection;
use Mpietrucha\Support\Concerns\HasFactory;

class Error
{
    use HasFactory;

    protected const BAG = 'default';

    protected string $bag = self::BAG;

    protected static ?Collection $errors = null;

    protected bool $wasPreviouslyUnpersisted = false;

    public function __construct(protected int $level, protected string $error, protected string $file, protected int $line)
    {
    }

    public function __destruct()
    {
        $this->persist();
    }

    public static function all(): Collection
    {
        return self::$errors ??= collect();
    }

    public static function clear(?string $bag = null): Collection
    {
        $errors = self::get($bag ?? self::BAG);

        self::all()->forget($bag);

        return $errors;
    }

    public static function get(?string $bag = null): Collection
    {
        return self::all()->get($bag ?? self::BAG, collect());
    }

    public function bag(?string $bag): self
    {
        $this->bag = $bag;

        return $this;
    }

    public function persist(): self
    {
        if ($this->wasPreviouslyUnpersisted) {
            return $this;
        }

        Macro::bootstrap();

        self::all()->list($this->bag, $this);

        return $this;
    }

    public function unpersist(): self
    {
        $this->wasPreviouslyUnpersisted = true;

        $current = self::get($this->bag)->search($this);

        if ($current !== false) {
            self::get($this->bag)->forget($current);
        }

        return $this;
    }

    public function level(): int
    {
        return $this->level;
    }

    public function error(): string
    {
        return $this->error;
    }

    public function file(): SplFileInfo
    {
        return File::toSplFileInfo($this->file);
    }

    public function line(): int
    {
        return $this->line;
    }
}

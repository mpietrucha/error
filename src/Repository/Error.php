<?php

namespace Mpietrucha\Error\Repository;

use SplFileInfo;
use Mpietrucha\Support\File;
use Mpietrucha\Support\Concerns\HasFactory;

class Error
{
    use HasFactory;

    public function __construct(protected int $level, protected string $error, protected string $file, protected int $line)
    {
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

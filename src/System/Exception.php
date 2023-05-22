<?php

namespace Mpietrucha\Error\System;

use Closure;

class Exception extends System
{
    protected static function setUsing(): Closure
    {
        return set_exception_handler(...);
    }

    protected static function restoreUsing(): Closure
    {
        return restore_exception_handler(...);
    }
}

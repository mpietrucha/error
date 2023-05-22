<?php

namespace Mpietrucha\Error\System;

use Closure;

class Error extends System
{
    protected static function setUsing(): Closure
    {
        return set_error_handler(...);
    }

    protected static function restoreUsing(): Closure
    {
        return restore_error_handler(...);
    }
}

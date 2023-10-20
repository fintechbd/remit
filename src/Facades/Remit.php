<?php

namespace Fintech\Remit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Fintech\Remit\Remit
 */
class Remit extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Fintech\Remit\Remit::class;
    }
}

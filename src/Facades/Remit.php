<?php

namespace Fintech\Remit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * // Crud Service Method Point Do not Remove //
 *
 * @see \Fintech\Remit\Remit
 */
class Remit extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Fintech\Remit\Remit::class;
    }
}

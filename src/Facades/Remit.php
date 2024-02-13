<?php

namespace Fintech\Remit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Fintech\Remit\Services\BankTransferService bankTransfer()
 * @method static \Fintech\Remit\Services\CashPickupService cashPickup()
 * @method static \Fintech\Remit\Services\WalletTransferService walletTransfer()
 *
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

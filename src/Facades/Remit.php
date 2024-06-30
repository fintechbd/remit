<?php

namespace Fintech\Remit\Facades;

use Fintech\Remit\Services\AssignVendorService;
use Fintech\Remit\Services\BankTransferService;
use Fintech\Remit\Services\CashPickupService;
use Fintech\Remit\Services\WalletTransferService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static BankTransferService bankTransfer()
 * @method static CashPickupService cashPickup()
 * @method static WalletTransferService walletTransfer()
 * @method static AssignVendorService assignVendor()
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

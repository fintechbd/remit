<?php

namespace Fintech\Remit;

use Fintech\Remit\Services\AssignVendorService;
use Fintech\Remit\Services\BankTransferService;
use Fintech\Remit\Services\CashPickupService;
use Fintech\Remit\Services\WalletTransferService;

class Remit
{
    /**
     * @return BankTransferService
     */
    public function bankTransfer($filters = null)
    {
        return \singleton(BankTransferService::class, $filters);
    }

    /**
     * @return CashPickupService
     */
    public function cashPickup($filters = null)
    {
        return \singleton(CashPickupService::class, $filters);
    }

    /**
     * @return WalletTransferService
     */
    public function walletTransfer($filters = null)
    {
        return \singleton(WalletTransferService::class, $filters);
    }

    /**
     * @return AssignVendorService
     */
    public function assignVendor($filters = null)
    {
        return \singleton(AssignVendorService::class, $filters);
    }

    //** Crud Service Method Point Do not Remove **//

}

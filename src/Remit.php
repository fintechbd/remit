<?php

namespace Fintech\Remit;

use Fintech\Remit\Services\BankTransferService;
use Fintech\Remit\Services\CashPickupService;
use Fintech\Remit\Services\WalletTransferService;

class Remit
{
    /**
     * @return BankTransferService
     */
    public function bankTransfer()
    {
        return app(BankTransferService::class);
    }

    /**
     * @return CashPickupService
     */
    public function cashPickup()
    {
        return app(CashPickupService::class);
    }

    /**
     * @return WalletTransferService
     */
    public function walletTransfer()
    {
        return app(WalletTransferService::class);
    }

    //** Crud Service Method Point Do not Remove **//

}

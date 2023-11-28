<?php

namespace Fintech\Remit;

class Remit
{
    /**
     * @return \Fintech\Remit\Services\BankTransferService
     */
    public function bankTransfer()
    {
        return app(\Fintech\Remit\Services\BankTransferService::class);
    }

    /**
     * @return \Fintech\Remit\Services\CashPickupService
     */
    public function cashPickup()
    {
        return app(\Fintech\Remit\Services\CashPickupService::class);
    }

    /**
     * @return \Fintech\Remit\Services\WalletTransferService
     */
    public function walletTransfer()
    {
        return app(\Fintech\Remit\Services\WalletTransferService::class);
    }

    //** Crud Service Method Point Do not Remove **//


}

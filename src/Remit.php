<?php

namespace Fintech\Remit;

use Fintech\Core\Abstracts\BaseModel;
use Fintech\Remit\Services\AssignVendorService;
use Fintech\Remit\Services\BankTransferService;
use Fintech\Remit\Services\CashPickupService;
use Fintech\Remit\Services\WalletTransferService;
use Illuminate\Database\Eloquent\Collection;

class Remit
{
    /**
     * @return BankTransferService|Collection|BaseModel
     */
    public function bankTransfer($filters = null)
    {
        return \singleton(BankTransferService::class, $filters);
    }

    /**
     * @return CashPickupService|Collection|BaseModel
     */
    public function cashPickup($filters = null)
    {
        return \singleton(CashPickupService::class, $filters);
    }

    /**
     * @return WalletTransferService|Collection|BaseModel
     */
    public function walletTransfer($filters = null)
    {
        return \singleton(WalletTransferService::class, $filters);
    }

    public function assignVendor()
    {
        return \app(AssignVendorService::class);
    }

    // ** Crud Service Method Point Do not Remove **//

}

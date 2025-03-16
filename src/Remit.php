<?php

namespace Fintech\Remit;

use Fintech\Core\Enums\Remit\AccountVerifyOption;
use Fintech\Remit\Contracts\CashPickupVerification;
use Fintech\Remit\Contracts\MoneyTransfer;
use Fintech\Remit\Contracts\WalletTransfer;
use Fintech\Remit\Services\AssignVendorService;
use Fintech\Remit\Services\BankTransferService;
use Fintech\Remit\Services\CashPickupService;
use Fintech\Remit\Services\WalletTransferService;
use Fintech\Remit\Support\AccountVerificationVerdict;

class Remit
{
    public function bankTransfer($filters = null)
    {
        return \singleton(BankTransferService::class, $filters);
    }

    public function cashPickup($filters = null)
    {
        return \singleton(CashPickupService::class, $filters);
    }

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

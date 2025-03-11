<?php

namespace Fintech\Remit\Facades;

use Fintech\Core\Enums\Reload\AccountVerifyOption;
use Fintech\Remit\Services\AssignVendorService;
use Fintech\Remit\Services\BankTransferService;
use Fintech\Remit\Services\CashPickupService;
use Fintech\Remit\Services\WalletTransferService;
use Fintech\Remit\Support\AccountVerificationVerdict;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Paginator|Collection|BankTransferService bankTransfer(array $filters = null)
 * @method static Paginator|Collection|CashPickupService cashPickup(array $filters = null)
 * @method static Paginator|Collection|WalletTransferService walletTransfer(array $filters = null)
 * @method static Paginator|Collection|AssignVendorService assignVendor(array $filters = null)
 * @method static AccountVerificationVerdict verifyAccount(AccountVerifyOption $type, array $inputs = [])
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

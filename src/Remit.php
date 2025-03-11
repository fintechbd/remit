<?php

namespace Fintech\Remit;

use Fintech\Core\Enums\Reload\AccountVerifyOption;
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

    /**
     * @throws \Exception
     */
    public function verifyAccount(AccountVerifyOption $type, array $inputs = []): AccountVerificationVerdict
    {
        $wallet = \Fintech\Banco\Facades\Banco::bank()->find($inputs['wallet_id']);

        $availableProviders = config('fintech.remit.providers');

        $provider = collect($availableProviders)->filter(function ($provider) use ($wallet) {
            return $provider['wallet_verification'] == true && in_array($wallet->country_id, $provider['countries'], true);
        })->first();

        if (! $provider) {
            throw new \ErrorException(__('remit::messages.verification.wallet_provider_not_found', ['wallet' => ucwords(strtolower($wallet->name))]));
        }

        $instance = app($provider['driver']);

        if (! $instance instanceof \Fintech\Remit\Contracts\AccountVerification) {
            throw new \ErrorException(__('remit::messages.verification.provider_missing_method', ['provider' => class_basename($provider['driver'])]));
        }

        $inputs['wallet'] = $wallet;

        unset($inputs['wallet_id']);

        return $instance->validateWallet($inputs);
    }

    // ** Crud Service Method Point Do not Remove **//

}

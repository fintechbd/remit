<?php

namespace Fintech\Remit;

use Fintech\Core\Enums\Reload\AccountVerifyOption;
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

    /**
     * @throws \Exception
     */
    public function verifyAccount(AccountVerifyOption $verifyType, array $inputs = []): AccountVerificationVerdict
    {
        $bank = \Fintech\Banco\Facades\Banco::bank()->firstWhere($inputs['slug']);
        $availableProviders = config('fintech.remit.providers');
        $provider = collect($availableProviders)->filter(function ($provider) use ($bank, $verifyType, $inputs) {
            if (in_array($bank->country_id, $provider['countries'], true) && in_array($inputs['slug'], $provider['banks'], true)) {
                return match ($verifyType) {
                    AccountVerifyOption::Wallet => $provider['wallet_verification'] == true,
                    AccountVerifyOption::BankTransfer => $provider['bank_transfer_verification'] == true,
                    AccountVerifyOption::CashPickup => $provider['cash_pickup_verification'] == true,
                    default => false
                };
            }

            return false;
        })->first();

        if (! $provider) {
            throw new \ErrorException(
                __('remit::messages.verification.wallet_provider_not_found',
                    ['wallet' => ucwords(strtolower($bank->name))]
                )
            );
        }

        $instance = app($provider['driver']);

        if (! $instance instanceof WalletTransfer) {
            throw new \ErrorException(
                __('remit::messages.verification.provider_missing_method', [
                    'type' => 'Wallet',
                    'provider' => class_basename($provider['driver']),
                ])
            );
        }

        if (! $instance instanceof MoneyTransfer) {
            throw new \ErrorException(
                __('remit::messages.verification.provider_missing_method', [
                    'type' => 'Bank Transfer',
                    'provider' => class_basename($provider['driver']),
                ])
            );
        }

        if (! $instance instanceof CashPickupVerification) {
            throw new \ErrorException(
                __('remit::messages.verification.provider_missing_method', [
                    'type' => 'Cash Pickup',
                    'provider' => class_basename($provider['driver']),
                ])
            );
        }

        $inputs['bank'] = $bank;

        unset($inputs['slug']);

        return match ($verifyType) {
            AccountVerifyOption::Wallet => $instance->validateWallet($inputs),
            AccountVerifyOption::BankTransfer => $instance->validateBankAccount($inputs),
            AccountVerifyOption::CashPickup => $instance->validateCashPickup($inputs),
            default => AccountVerificationVerdict::make()
        };
    }

    // ** Crud Service Method Point Do not Remove **//

}

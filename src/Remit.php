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

    /**
     * @throws \Exception
     */
    public function verifyAccount(AccountVerifyOption $verifyType, array $inputs = []): AccountVerificationVerdict
    {
        $bank = \Fintech\Banco\Facades\Banco::bank()->findWhere([
            'slug' => $inputs['slug'],
            'enabled' => true,
        ]);

        $availableProviders = collect(config('fintech.remit.providers'));

        $provider = $availableProviders->filter(function ($agent) use ($bank, $verifyType) {
            if (in_array($bank->country_id, $agent['countries']) && in_array($bank->slug, $agent['banks'])) {
                return match ($verifyType) {
                    AccountVerifyOption::WalletTransfer => $agent['wallet_verification'] == true,
                    AccountVerifyOption::BankTransfer => $agent['bank_transfer_verification'] == true,
                    AccountVerifyOption::CashPickup => $agent['cash_pickup_verification'] == true,
                    default => false
                };
            }
            return false;
        })->first();

        dd($provider);

        if (! $provider) {
            throw new \ErrorException(
                __('remit::messages.verification.wallet_provider_not_found',
                    ['wallet' => ucwords(strtolower($bank->name))]
                )
            );
        }

        $instance = app($provider['driver']);

        $inputs['bank'] = $bank->toArray();

        unset($inputs['slug']);

        switch ($verifyType) {
            case AccountVerifyOption::WalletTransfer :

                if (! $instance instanceof WalletTransfer) {
                    throw new \ErrorException(
                        __('remit::messages.verification.provider_missing_method', [
                            'type' => 'Wallet',
                            'provider' => class_basename($provider['driver']),
                        ])
                    );
                }

                return $instance->validateWallet($inputs);

            case AccountVerifyOption::BankTransfer :

                $inputs['bank_branch'] = \Fintech\Banco\Facades\Banco::bankBranch()->find($inputs['branch_id'])?->toArray() ?? [];

                $inputs['beneficiary_account_type'] = \Fintech\Banco\Facades\Banco::beneficiaryAccountType()->find($inputs['account_type_id'])?->toArray() ?? [];

                if (! $instance instanceof MoneyTransfer) {
                    throw new \ErrorException(
                        __('remit::messages.verification.provider_missing_method', [
                            'type' => 'Bank Transfer',
                            'provider' => class_basename($provider['driver']),
                        ])
                    );
                }

                return $instance->validateBankAccount($inputs);

            case AccountVerifyOption::CashPickup :

                if (! $instance instanceof CashPickupVerification) {
                    throw new \ErrorException(
                        __('remit::messages.verification.provider_missing_method', [
                            'type' => 'Cash Pickup',
                            'provider' => class_basename($provider['driver']),
                        ])
                    );
                }

                return $instance->validateCashPickup($inputs);

            default:

                return AccountVerificationVerdict::make();

        }

    }

    // ** Crud Service Method Point Do not Remove **//

}

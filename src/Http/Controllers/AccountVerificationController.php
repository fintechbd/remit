<?php

namespace Fintech\Remit\Http\Controllers;

use Exception;
use Fintech\Core\Enums\Remit\AccountVerifyOption;
use Fintech\Remit\Facades\Remit;
use Fintech\Remit\Http\Requests\BankTransferVerificationRequest;
use Fintech\Remit\Http\Requests\CashPickupVerificationRequest;
use Fintech\Remit\Http\Requests\WalletVerificationRequest;
use Fintech\Remit\Http\Resources\AccountVerificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AccountVerificationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function wallet(WalletVerificationRequest $request): JsonResponse|AccountVerificationResource
    {

        try {
            $verification = Remit::verifyAccount(AccountVerifyOption::Wallet, $request->validated());

            if (! $verification->status) {
                throw new Exception($verification->message);
            }

            return new AccountVerificationResource($verification);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * Handle the incoming request.
     */
    public function bankTransfer(BankTransferVerificationRequest $request): JsonResponse|AccountVerificationResource
    {

        try {
            $verification = Remit::verifyAccount(AccountVerifyOption::BankTransfer, $request->validated());

            if (! $verification->status) {
                throw new Exception($verification->message);
            }

            return new AccountVerificationResource($verification);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * Handle the incoming request.
     */
    public function cashPickup(CashPickupVerificationRequest $request): JsonResponse|AccountVerificationResource
    {

        try {
            $verification = Remit::verifyAccount(AccountVerifyOption::CashPickup, $request->validated());

            if (! $verification->status) {
                throw new Exception($verification->message);
            }

            return new AccountVerificationResource($verification);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }
}

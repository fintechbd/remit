<?php

namespace Fintech\Remit\Http\Controllers;

use Exception;
use Fintech\Banco\Facades\Banco;
use Fintech\Remit\Contracts\WalletVerification;
use Fintech\Remit\Facades\Remit;
use Fintech\Remit\Http\Requests\WalletVerificationRequest;
use Fintech\Remit\Http\Resources\WalletVerificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class WalletVerificationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(WalletVerificationRequest $request): JsonResponse|WalletVerificationResource
    {

        try {
            $verification = Remit::verifyWallet($request->validated());

            if (!$verification->status) {
                throw new Exception($verification->message);
            }

            return new WalletVerificationResource($verification);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }
}

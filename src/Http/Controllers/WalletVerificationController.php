<?php

namespace Fintech\Remit\Http\Controllers;

use Exception;
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

            if (strlen($request->input('wallet_no')) < 10) {
                throw new Exception('Invalid Bkash Wallet Number.');
            }

            $data = [
                'name' => 'Bkash',
                'account_title' => 'MT TECHNOLOGIES LTD',
                'account_no' => $request->input('wallet_no', '01689553434'),
            ];

            return new WalletVerificationResource($data);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }
}

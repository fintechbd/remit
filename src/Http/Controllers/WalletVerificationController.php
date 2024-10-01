<?php

namespace Fintech\Remit\Http\Controllers;

use Exception;
use Fintech\Banco\Facades\Banco;
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
            $wallet = Banco::bank()->find($request->input('wallet_id'));

            $data['name'] = $wallet->name ?? null;

            if ($request->input('wallet_no') == '01689553434') {
                $data['account_title'] = $request->user('sanctum')->name ?? null;
                $data['account_no'] = $request->input('wallet_no');
            } else {
                throw new Exception('Wallet Verification failed');
            }

            return new WalletVerificationResource($data);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }
}

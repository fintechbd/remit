<?php

namespace Fintech\Remit\Http\Controllers;


use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Remit\Http\Requests\WalletVerificationRequest;
use Illuminate\Routing\Controller;

class WalletVerificationController extends Controller
{
    use ApiResponseTrait;

    /**
     * Handle the incoming request.
     */
    public function __invoke(WalletVerificationRequest $request)
    {

        try {

            if (strlen($request->input('wallet_no')) < 10) {
                throw new \Exception('Invalid Bkash Wallet Number.');
            }

            $data = [
                'data' => [
                    'name' => 'Bkash',
                    'account_title' => 'MT TECHNOLOGIES LTD',
                    'account_no' => $request->input('wallet_no', '01689553434')
                ]
            ];

            return $this->success($data);

        } catch (\Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }
}

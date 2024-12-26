<?php

namespace Fintech\Remit\Http\Controllers;

use Fintech\Remit\Http\Requests\MoneyTransferPaymentRequest;
use Illuminate\Routing\Controller;

class MoneyTransferPaymentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(string $id, MoneyTransferPaymentRequest $request)
    {
        //
    }
}

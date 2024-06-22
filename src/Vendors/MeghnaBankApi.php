<?php

namespace Fintech\Remit\Vendors;

use Exception;
use Fintech\Remit\Contracts\BankTransfer;
use Fintech\Remit\Contracts\OrderQuotation;
use Illuminate\Support\Facades\Log;

class MeghnaBankApi implements BankTransfer, OrderQuotation
{
    public function makeTransfer(array $orderInfo = []): mixed
    {
        // TODO: Implement makeTransfer() method.
    }

    public function transferStatus(array $orderInfo = []): mixed
    {
        // TODO: Implement transferStatus() method.
    }

    public function cancelTransfer(array $orderInfo = []): mixed
    {
        // TODO: Implement cancelTransfer() method.
    }

    public function verifyAccount(array $accountInfo = []): mixed
    {
        // TODO: Implement verifyAccount() method.
    }

    public function vendorBalance(array $accountInfo = []): mixed
    {
        // TODO: Implement vendorBalance() method.
    }

    public function requestQuotation($order): mixed
    {
        // TODO: Implement requestQuotation() method.
    }



}

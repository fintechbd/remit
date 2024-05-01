<?php

namespace Fintech\Remit\Contracts;

interface BankTransfer
{
    /**
     * Execute the transfer operation
     */
    public function makeTransfer(array $orderInfo = []): mixed;

    public function transferStatus(array $orderInfo = []): mixed;

    public function cancelTransfer(array $orderInfo = []): mixed;

    public function verifyAccount(array $accountInfo = []): mixed;

    public function vendorBalance(array $accountInfo = []): mixed;
}

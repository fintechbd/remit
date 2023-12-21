<?php

namespace Fintech\Remit\Contracts;

interface BankTransfer
{
    /**
     * Execute the transfer operation
     *
     * @param array $orderInfo
     * @return mixed
     */
    public function makeTransfer(array $orderInfo): mixed;

    /**
     * @param array $orderInfo
     * @return mixed
     */
    public function transferStatus(array $orderInfo): mixed;

    /**
     * @param array $orderInfo
     * @return mixed
     */
    public function cancelTransfer(array $orderInfo): mixed;

    /**
     * @param array $accountInfo
     * @return mixed
     */
    public function verifyAccount(array $accountInfo): mixed;

    /**
     * @param array $accountInfo
     * @return mixed
     */
    public function vendorBalance(array $accountInfo): mixed;
}

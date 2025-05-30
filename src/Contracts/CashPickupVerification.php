<?php

namespace Fintech\Remit\Contracts;

use Fintech\Remit\Support\AccountVerificationVerdict;

interface CashPickupVerification
{
    /**
     * Method to make a request to the remittance service provider
     * for a quotation of the order. that include charge, fee,
     * commission and other information related to order.
     *
     * @throws \ErrorException
     */
    public function validateCashPickup(array $inputs = []): AccountVerificationVerdict;
}

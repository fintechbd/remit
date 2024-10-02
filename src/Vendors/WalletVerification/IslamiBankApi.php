<?php

namespace Fintech\Remit\Vendors\WalletVerification;

use Fintech\Remit\Contracts\WalletVerification;
use Fintech\Remit\Support\WalletVerificationVerdict;
use Fintech\Remit\Vendors\IslamiBankAuthTrait;

class IslamiBankApi implements WalletVerification
{
    use IslamiBankAuthTrait;

    /**
     * Run a wallet verification
     * and return conclusion if it is valid or not
     */
    public function __invoke(): WalletVerificationVerdict {}
}

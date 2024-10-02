<?php

namespace Fintech\Remit\Vendors\WalletVerification;

use DOMDocument;
use DOMException;
use ErrorException;
use Exception;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Supports\Utility;
use Fintech\Remit\Contracts\MoneyTransfer;
use Fintech\Remit\Contracts\WalletTransfer;
use Fintech\Remit\Contracts\WalletVerification;
use Fintech\Remit\Support\WalletVerificationVerdict;
use Fintech\Remit\Vendors\IslamiBankAuthTrait;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class IslamiBankApi implements WalletVerification
{
   use IslamiBankAuthTrait;


    /**
     * Run a wallet verification
     * and return conclusion if it is valid or not
     *
     * @return WalletVerificationVerdict
     */
    public function __invoke(): WalletVerificationVerdict
    {

    }
}

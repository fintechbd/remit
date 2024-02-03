<?php

namespace Fintech\Remit\Repositories\Eloquent;

use Fintech\Remit\Interfaces\BankTransferRepository as InterfacesBankTransferRepository;
use Fintech\Transaction\Repositories\Eloquent\OrderRepository;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Class BankTransferRepository
 */
class BankTransferRepository extends OrderRepository implements InterfacesBankTransferRepository
{
    public function __construct()
    {
        $model = app(config('fintech.remit.bank_transfer_model', \Fintech\Remit\Models\BankTransfer::class));

        if (! $model instanceof Model) {
            throw new InvalidArgumentException("Eloquent repository require model class to be `Illuminate\Database\Eloquent\Model` instance.");
        }

        $this->model = $model;
    }
}

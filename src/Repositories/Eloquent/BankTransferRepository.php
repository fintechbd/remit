<?php

namespace Fintech\Remit\Repositories\Eloquent;

use Fintech\Remit\Interfaces\BankTransferRepository as InterfacesBankTransferRepository;
use Fintech\Remit\Models\BankTransfer;
use Fintech\Transaction\Repositories\Eloquent\OrderRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class BankTransferRepository
 */
class BankTransferRepository extends OrderRepository implements InterfacesBankTransferRepository
{
    public function __construct()
    {
        parent::__construct(config('fintech.remit.bank_transfer_model', BankTransfer::class));
    }

    /**
     * return a list or pagination of items from
     * filtered options
     *
     * @return Paginator|Collection
     *
     * @throws BindingResolutionException
     */
    public function list(array $filters = [])
    {
        return parent::list($filters);

    }
}

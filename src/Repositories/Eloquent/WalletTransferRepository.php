<?php

namespace Fintech\Remit\Repositories\Eloquent;

use Fintech\Remit\Interfaces\WalletTransferRepository as InterfacesWalletTransferRepository;
use Fintech\Remit\Models\WalletTransfer;
use Fintech\Transaction\Repositories\Eloquent\OrderRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

/**
 * Class WalletTransferRepository
 */
class WalletTransferRepository extends OrderRepository implements InterfacesWalletTransferRepository
{
    public function __construct()
    {
        parent::__construct(config('fintech.remit.wallet_transfer_model', WalletTransfer::class));
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

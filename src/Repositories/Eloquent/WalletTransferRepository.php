<?php

namespace Fintech\Remit\Repositories\Eloquent;

use Fintech\Core\Repositories\EloquentRepository;
use Fintech\Remit\Interfaces\WalletTransferRepository as InterfacesWalletTransferRepository;
use Fintech\Transaction\Repositories\Eloquent\OrderRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Class WalletTransferRepository
 */
class WalletTransferRepository extends OrderRepository implements InterfacesWalletTransferRepository
{
    public function __construct()
    {
        $model = app(config('fintech.remit.wallet_transfer_model', \Fintech\Remit\Models\WalletTransfer::class));

        if (! $model instanceof Model) {
            throw new InvalidArgumentException("Eloquent repository require model class to be `Illuminate\Database\Eloquent\Model` instance.");
        }

        $this->model = $model;
    }

    /**
     * return a list or pagination of items from
     * filtered options
     *
     * @return Paginator|Collection
     * @throws BindingResolutionException
     */
    public function list(array $filters = [])
    {
        return parent::list($filters);

    }
}

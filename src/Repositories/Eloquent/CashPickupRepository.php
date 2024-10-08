<?php

namespace Fintech\Remit\Repositories\Eloquent;

use Fintech\Remit\Interfaces\CashPickupRepository as InterfacesCashPickupRepository;
use Fintech\Remit\Models\CashPickup;
use Fintech\Transaction\Repositories\Eloquent\OrderRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class CashPickupRepository
 */
class CashPickupRepository extends OrderRepository implements InterfacesCashPickupRepository
{
    public function __construct()
    {
        parent::__construct(config('fintech.remit.cash_pickup_model', CashPickup::class));
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

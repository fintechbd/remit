<?php

namespace Fintech\Remit\Repositories\Eloquent;

use Fintech\Remit\Interfaces\CashPickupRepository as InterfacesCashPickupRepository;
use Fintech\Remit\Models\CashPickup;
use Fintech\Transaction\Repositories\Eloquent\OrderRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

/**
 * Class CashPickupRepository
 */
class CashPickupRepository extends OrderRepository implements InterfacesCashPickupRepository
{
    public function __construct()
    {
        $model = app(config('fintech.remit.cash_pickup_model', CashPickup::class));

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
     *
     * @throws BindingResolutionException
     */
    public function list(array $filters = [])
    {
        return parent::list($filters);

    }
}

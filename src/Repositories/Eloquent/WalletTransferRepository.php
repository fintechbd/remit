<?php

namespace Fintech\Remit\Repositories\Eloquent;

use Fintech\Core\Repositories\EloquentRepository;
use Fintech\Remit\Interfaces\WalletTransferRepository as InterfacesWalletTransferRepository;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Class WalletTransferRepository
 */
class WalletTransferRepository extends EloquentRepository implements InterfacesWalletTransferRepository
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
     */
    public function list(array $filters = [])
    {
        $query = $this->model->newQuery();

        //Searching
        if (! empty($filters['search'])) {
            if (is_numeric($filters['search'])) {
                $query->where($this->model->getKeyName(), 'like', "%{$filters['search']}%");
            } else {
                $query->where('name', 'like', "%{$filters['search']}%");
                $query->orWhere('wallet_transfer_data', 'like', "%{$filters['search']}%");
            }
        }

        if (! empty($filters['transaction_form_id'])) {
            $query->where('transaction_form_id', $filters['transaction_form_id']);
        }

        //Display Trashed
        if (isset($filters['trashed']) && $filters['trashed'] === true) {
            $query->onlyTrashed();
        }

        //Handle Sorting
        $query->orderBy($filters['sort'] ?? $this->model->getKeyName(), $filters['dir'] ?? 'asc');

        //Execute Output
        return $this->executeQuery($query, $filters);

    }
}

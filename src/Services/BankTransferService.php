<?php

namespace Fintech\Remit\Services;

use Fintech\Remit\Interfaces\BankTransferRepository;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class BankTransferService
 * @property BankTransferRepository $bankTransferRepository
 */
class BankTransferService
{
    /**
     * BankTransferService constructor.
     * @param BankTransferRepository $bankTransferRepository
     */
    public function __construct(BankTransferRepository $bankTransferRepository)
    {
        $this->bankTransferRepository = $bankTransferRepository;
    }

    /**
     * @param array $filters
     * @return Collection|Paginator
     */
    public function list(array $filters = []): Collection|Paginator
    {
        return $this->bankTransferRepository->list($filters);

    }

    /**
     * @param array $inputs
     * @return Model|\MongoDB\Laravel\Eloquent\Model|null
     */
    public function create(array $inputs = []): Model|\MongoDB\Laravel\Eloquent\Model|null
    {
        return $this->bankTransferRepository->create($inputs);
    }

    /**
     * @param $id
     * @param bool $onlyTrashed
     * @return Model|\MongoDB\Laravel\Eloquent\Model|null
     */
    public function find($id, bool $onlyTrashed = false): Model|\MongoDB\Laravel\Eloquent\Model|null
    {
        return $this->bankTransferRepository->find($id, $onlyTrashed);
    }

    /**
     * @param $id
     * @param array $inputs
     * @return Model|\MongoDB\Laravel\Eloquent\Model|null
     */
    public function update($id, array $inputs = []): Model|\MongoDB\Laravel\Eloquent\Model|null
    {
        return $this->bankTransferRepository->update($id, $inputs);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function destroy($id)
    {
        return $this->bankTransferRepository->delete($id);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function restore($id)
    {
        return $this->bankTransferRepository->restore($id);
    }

    /**
     * @param array $filters
     * @return Paginator|Collection
     */
    public function export(array $filters): Paginator|Collection
    {
        return $this->bankTransferRepository->list($filters);
    }

    /**
     * @param array $filters
     * @return Model|\MongoDB\Laravel\Eloquent\Model|null
     */
    public function import(array $filters): Model|\MongoDB\Laravel\Eloquent\Model|null
    {
        return $this->bankTransferRepository->create($filters);
    }
}

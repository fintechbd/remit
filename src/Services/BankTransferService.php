<?php

namespace Fintech\Remit\Services;

use Fintech\Remit\Interfaces\BankTransferRepository;

/**
 * Class BankTransferService
 */
class BankTransferService
{
    /**
     * BankTransferService constructor.
     */
    public function __construct(BankTransferRepository $bankTransferRepository)
    {
        $this->bankTransferRepository = $bankTransferRepository;
    }

    /**
     * @return mixed
     */
    public function list(array $filters = [])
    {
        return $this->bankTransferRepository->list($filters);

    }

    public function create(array $inputs = [])
    {
        return $this->bankTransferRepository->create($inputs);
    }

    public function find($id, $onlyTrashed = false)
    {
        return $this->bankTransferRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = [])
    {
        return $this->bankTransferRepository->update($id, $inputs);
    }

    public function destroy($id)
    {
        return $this->bankTransferRepository->delete($id);
    }

    public function restore($id)
    {
        return $this->bankTransferRepository->restore($id);
    }

    public function export(array $filters)
    {
        return $this->bankTransferRepository->list($filters);
    }

    public function import(array $filters)
    {
        return $this->bankTransferRepository->create($filters);
    }
}

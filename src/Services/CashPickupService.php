<?php

namespace Fintech\Remit\Services;


use Fintech\Remit\Interfaces\CashPickupRepository;

/**
 * Class CashPickupService
 * @package Fintech\Remit\Services
 *
 */
class CashPickupService
{
    /**
     * CashPickupService constructor.
     * @param CashPickupRepository $cashPickupRepository
     */
    public function __construct(CashPickupRepository $cashPickupRepository) {
        $this->cashPickupRepository = $cashPickupRepository;
    }

    /**
     * @param array $filters
     * @return mixed
     */
    public function list(array $filters = [])
    {
        return $this->cashPickupRepository->list($filters);

    }

    public function create(array $inputs = [])
    {
        return $this->cashPickupRepository->create($inputs);
    }

    public function find($id, $onlyTrashed = false)
    {
        return $this->cashPickupRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = [])
    {
        return $this->cashPickupRepository->update($id, $inputs);
    }

    public function destroy($id)
    {
        return $this->cashPickupRepository->delete($id);
    }

    public function restore($id)
    {
        return $this->cashPickupRepository->restore($id);
    }

    public function export(array $filters)
    {
        return $this->cashPickupRepository->list($filters);
    }

    public function import(array $filters)
    {
        return $this->cashPickupRepository->create($filters);
    }
}

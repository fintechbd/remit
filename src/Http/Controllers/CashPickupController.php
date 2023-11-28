<?php

namespace Fintech\Remit\Http\Controllers;
use Exception;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Remit\Facades\Remit;
use Fintech\Remit\Http\Resources\CashPickupResource;
use Fintech\Remit\Http\Resources\CashPickupCollection;
use Fintech\Remit\Http\Requests\ImportCashPickupRequest;
use Fintech\Remit\Http\Requests\StoreCashPickupRequest;
use Fintech\Remit\Http\Requests\UpdateCashPickupRequest;
use Fintech\Remit\Http\Requests\IndexCashPickupRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Class CashPickupController
 * @package Fintech\Remit\Http\Controllers
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to CashPickup
 * @lrd:end
 *
 */

class CashPickupController extends Controller
{
    use ApiResponseTrait;

    /**
     * @lrd:start
     * Return a listing of the *CashPickup* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     * @lrd:end
     *
     * @param IndexCashPickupRequest $request
     * @return CashPickupCollection|JsonResponse
     */
    public function index(IndexCashPickupRequest $request): CashPickupCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $cashPickupPaginate = Remit::cashPickup()->list($inputs);

            return new CashPickupCollection($cashPickupPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a new *CashPickup* resource in storage.
     * @lrd:end
     *
     * @param StoreCashPickupRequest $request
     * @return JsonResponse
     * @throws StoreOperationException
     */
    public function store(StoreCashPickupRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $cashPickup = Remit::cashPickup()->create($inputs);

            if (!$cashPickup) {
                throw (new StoreOperationException)->setModel(config('fintech.remit.cash_pickup_model'));
            }

            return $this->created([
                'message' => __('core::messages.resource.created', ['model' => 'Cash Pickup']),
                'id' => $cashPickup->id
             ]);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Return a specified *CashPickup* resource found by id.
     * @lrd:end
     *
     * @param string|int $id
     * @return CashPickupResource|JsonResponse
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): CashPickupResource|JsonResponse
    {
        try {

            $cashPickup = Remit::cashPickup()->find($id);

            if (!$cashPickup) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            return new CashPickupResource($cashPickup);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Update a specified *CashPickup* resource using id.
     * @lrd:end
     *
     * @param UpdateCashPickupRequest $request
     * @param string|int $id
     * @return JsonResponse
     * @throws ModelNotFoundException
     * @throws UpdateOperationException
     */
    public function update(UpdateCashPickupRequest $request, string|int $id): JsonResponse
    {
        try {

            $cashPickup = Remit::cashPickup()->find($id);

            if (!$cashPickup) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            $inputs = $request->validated();

            if (!Remit::cashPickup()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            return $this->updated(__('core::messages.resource.updated', ['model' => 'Cash Pickup']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *CashPickup* resource using id.
     * @lrd:end
     *
     * @param string|int $id
     * @return JsonResponse
     * @throws ModelNotFoundException
     * @throws DeleteOperationException
     */
    public function destroy(string|int $id)
    {
        try {

            $cashPickup = Remit::cashPickup()->find($id);

            if (!$cashPickup) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            if (!Remit::cashPickup()->destroy($id)) {

                throw (new DeleteOperationException())->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            return $this->deleted(__('core::messages.resource.deleted', ['model' => 'Cash Pickup']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Restore the specified *CashPickup* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     * @lrd:end
     *
     * @param string|int $id
     * @return JsonResponse
     */
    public function restore(string|int $id)
    {
        try {

            $cashPickup = Remit::cashPickup()->find($id, true);

            if (!$cashPickup) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            if (!Remit::cashPickup()->restore($id)) {

                throw (new RestoreOperationException())->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            return $this->restored(__('core::messages.resource.restored', ['model' => 'Cash Pickup']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *CashPickup* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @param IndexCashPickupRequest $request
     * @return JsonResponse
     */
    public function export(IndexCashPickupRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $cashPickupPaginate = Remit::cashPickup()->export($inputs);

            return $this->exported(__('core::messages.resource.exported', ['model' => 'Cash Pickup']));

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *CashPickup* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @param ImportCashPickupRequest $request
     * @return CashPickupCollection|JsonResponse
     */
    public function import(ImportCashPickupRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $cashPickupPaginate = Remit::cashPickup()->list($inputs);

            return new CashPickupCollection($cashPickupPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }
}

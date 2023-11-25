<?php

namespace Fintech\Remit\Http\Controllers;
use Exception;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Remit\Facades\Remit;
use Fintech\Remit\Http\Resources\BankTransferResource;
use Fintech\Remit\Http\Resources\BankTransferCollection;
use Fintech\Remit\Http\Requests\ImportBankTransferRequest;
use Fintech\Remit\Http\Requests\StoreBankTransferRequest;
use Fintech\Remit\Http\Requests\UpdateBankTransferRequest;
use Fintech\Remit\Http\Requests\IndexBankTransferRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Class BankTransferController
 * @package Fintech\Remit\Http\Controllers
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to BankTransfer
 * @lrd:end
 *
 */

class BankTransferController extends Controller
{
    use ApiResponseTrait;

    /**
     * @lrd:start
     * Return a listing of the *BankTransfer* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     * @lrd:end
     *
     * @param IndexBankTransferRequest $request
     * @return BankTransferCollection|JsonResponse
     */
    public function index(IndexBankTransferRequest $request): BankTransferCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $bankTransferPaginate = Remit::bankTransfer()->list($inputs);

            return new BankTransferCollection($bankTransferPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a new *BankTransfer* resource in storage.
     * @lrd:end
     *
     * @param StoreBankTransferRequest $request
     * @return JsonResponse
     * @throws StoreOperationException
     */
    public function store(StoreBankTransferRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $bankTransfer = Remit::bankTransfer()->create($inputs);

            if (!$bankTransfer) {
                throw (new StoreOperationException)->setModel(config('fintech.remit.bank_transfer_model'));
            }

            return $this->created([
                'message' => __('core::messages.resource.created', ['model' => 'Bank Transfer']),
                'id' => $bankTransfer->id
             ]);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Return a specified *BankTransfer* resource found by id.
     * @lrd:end
     *
     * @param string|int $id
     * @return BankTransferResource|JsonResponse
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): BankTransferResource|JsonResponse
    {
        try {

            $bankTransfer = Remit::bankTransfer()->find($id);

            if (!$bankTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.bank_transfer_model'), $id);
            }

            return new BankTransferResource($bankTransfer);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Update a specified *BankTransfer* resource using id.
     * @lrd:end
     *
     * @param UpdateBankTransferRequest $request
     * @param string|int $id
     * @return JsonResponse
     * @throws ModelNotFoundException
     * @throws UpdateOperationException
     */
    public function update(UpdateBankTransferRequest $request, string|int $id): JsonResponse
    {
        try {

            $bankTransfer = Remit::bankTransfer()->find($id);

            if (!$bankTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.bank_transfer_model'), $id);
            }

            $inputs = $request->validated();

            if (!Remit::bankTransfer()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.remit.bank_transfer_model'), $id);
            }

            return $this->updated(__('core::messages.resource.updated', ['model' => 'Bank Transfer']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *BankTransfer* resource using id.
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

            $bankTransfer = Remit::bankTransfer()->find($id);

            if (!$bankTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.bank_transfer_model'), $id);
            }

            if (!Remit::bankTransfer()->destroy($id)) {

                throw (new DeleteOperationException())->setModel(config('fintech.remit.bank_transfer_model'), $id);
            }

            return $this->deleted(__('core::messages.resource.deleted', ['model' => 'Bank Transfer']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Restore the specified *BankTransfer* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     * @lrd:end
     *
     * @param string|int $id
     * @return JsonResponse
     */
    public function restore(string|int $id)
    {
        try {

            $bankTransfer = Remit::bankTransfer()->find($id, true);

            if (!$bankTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.bank_transfer_model'), $id);
            }

            if (!Remit::bankTransfer()->restore($id)) {

                throw (new RestoreOperationException())->setModel(config('fintech.remit.bank_transfer_model'), $id);
            }

            return $this->restored(__('core::messages.resource.restored', ['model' => 'Bank Transfer']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *BankTransfer* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @param IndexBankTransferRequest $request
     * @return JsonResponse
     */
    public function export(IndexBankTransferRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $bankTransferPaginate = Remit::bankTransfer()->export($inputs);

            return $this->exported(__('core::messages.resource.exported', ['model' => 'Bank Transfer']));

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *BankTransfer* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @param ImportBankTransferRequest $request
     * @return BankTransferCollection|JsonResponse
     */
    public function import(ImportBankTransferRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $bankTransferPaginate = Remit::bankTransfer()->list($inputs);

            return new BankTransferCollection($bankTransferPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }
}

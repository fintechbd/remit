<?php

namespace Fintech\Remit\Http\Controllers;
use Exception;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Remit\Facades\Remit;
use Fintech\Remit\Http\Resources\WalletTransferResource;
use Fintech\Remit\Http\Resources\WalletTransferCollection;
use Fintech\Remit\Http\Requests\ImportWalletTransferRequest;
use Fintech\Remit\Http\Requests\StoreWalletTransferRequest;
use Fintech\Remit\Http\Requests\UpdateWalletTransferRequest;
use Fintech\Remit\Http\Requests\IndexWalletTransferRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Class WalletTransferController
 * @package Fintech\Remit\Http\Controllers
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to WalletTransfer
 * @lrd:end
 *
 */

class WalletTransferController extends Controller
{
    use ApiResponseTrait;

    /**
     * @lrd:start
     * Return a listing of the *WalletTransfer* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     * @lrd:end
     *
     * @param IndexWalletTransferRequest $request
     * @return WalletTransferCollection|JsonResponse
     */
    public function index(IndexWalletTransferRequest $request): WalletTransferCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletTransferPaginate = Remit::walletTransfer()->list($inputs);

            return new WalletTransferCollection($walletTransferPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a new *WalletTransfer* resource in storage.
     * @lrd:end
     *
     * @param StoreWalletTransferRequest $request
     * @return JsonResponse
     * @throws StoreOperationException
     */
    public function store(StoreWalletTransferRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletTransfer = Remit::walletTransfer()->create($inputs);

            if (!$walletTransfer) {
                throw (new StoreOperationException)->setModel(config('fintech.remit.wallet_transfer_model'));
            }

            return $this->created([
                'message' => __('core::messages.resource.created', ['model' => 'Wallet Transfer']),
                'id' => $walletTransfer->id
             ]);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Return a specified *WalletTransfer* resource found by id.
     * @lrd:end
     *
     * @param string|int $id
     * @return WalletTransferResource|JsonResponse
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): WalletTransferResource|JsonResponse
    {
        try {

            $walletTransfer = Remit::walletTransfer()->find($id);

            if (!$walletTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.wallet_transfer_model'), $id);
            }

            return new WalletTransferResource($walletTransfer);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Update a specified *WalletTransfer* resource using id.
     * @lrd:end
     *
     * @param UpdateWalletTransferRequest $request
     * @param string|int $id
     * @return JsonResponse
     * @throws ModelNotFoundException
     * @throws UpdateOperationException
     */
    public function update(UpdateWalletTransferRequest $request, string|int $id): JsonResponse
    {
        try {

            $walletTransfer = Remit::walletTransfer()->find($id);

            if (!$walletTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.wallet_transfer_model'), $id);
            }

            $inputs = $request->validated();

            if (!Remit::walletTransfer()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.remit.wallet_transfer_model'), $id);
            }

            return $this->updated(__('core::messages.resource.updated', ['model' => 'Wallet Transfer']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *WalletTransfer* resource using id.
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

            $walletTransfer = Remit::walletTransfer()->find($id);

            if (!$walletTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.wallet_transfer_model'), $id);
            }

            if (!Remit::walletTransfer()->destroy($id)) {

                throw (new DeleteOperationException())->setModel(config('fintech.remit.wallet_transfer_model'), $id);
            }

            return $this->deleted(__('core::messages.resource.deleted', ['model' => 'Wallet Transfer']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Restore the specified *WalletTransfer* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     * @lrd:end
     *
     * @param string|int $id
     * @return JsonResponse
     */
    public function restore(string|int $id)
    {
        try {

            $walletTransfer = Remit::walletTransfer()->find($id, true);

            if (!$walletTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.wallet_transfer_model'), $id);
            }

            if (!Remit::walletTransfer()->restore($id)) {

                throw (new RestoreOperationException())->setModel(config('fintech.remit.wallet_transfer_model'), $id);
            }

            return $this->restored(__('core::messages.resource.restored', ['model' => 'Wallet Transfer']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *WalletTransfer* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @param IndexWalletTransferRequest $request
     * @return JsonResponse
     */
    public function export(IndexWalletTransferRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletTransferPaginate = Remit::walletTransfer()->export($inputs);

            return $this->exported(__('core::messages.resource.exported', ['model' => 'Wallet Transfer']));

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *WalletTransfer* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @param ImportWalletTransferRequest $request
     * @return WalletTransferCollection|JsonResponse
     */
    public function import(ImportWalletTransferRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletTransferPaginate = Remit::walletTransfer()->list($inputs);

            return new WalletTransferCollection($walletTransferPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }
}

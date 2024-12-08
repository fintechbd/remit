<?php

namespace Fintech\Remit\Http\Controllers;

use Exception;
use Fintech\Auth\Facades\Auth;
use Fintech\Banco\Facades\Banco;
use Fintech\Business\Facades\Business;
use Fintech\Core\Enums\Auth\RiskProfile;
use Fintech\Core\Enums\Auth\SystemRole;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Remit\Facades\Remit;
use Fintech\Remit\Http\Requests\ImportWalletTransferRequest;
use Fintech\Remit\Http\Requests\IndexWalletTransferRequest;
use Fintech\Remit\Http\Requests\StoreWalletTransferRequest;
use Fintech\Remit\Http\Requests\UpdateWalletTransferRequest;
use Fintech\Remit\Http\Resources\WalletTransferCollection;
use Fintech\Remit\Http\Resources\WalletTransferResource;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Class WalletTransferController
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to WalletTransfer
 *
 * @lrd:end
 */
class WalletTransferController extends Controller
{
    public function __construct()
    {
        $this->middleware('imposter', ['only' => ['store']]);
    }

    /**
     * @lrd:start
     * Return a listing of the *WalletTransfer* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     *
     * @lrd:end
     */
    public function index(IndexWalletTransferRequest $request): WalletTransferCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $inputs['transaction_form_id'] = Transaction::transactionForm()->findWhere(['code' => 'wallet_transfer'])->getKey();

            if ($request->isAgent()) {
                $inputs['creator_id'] = $request->user('sanctum')->getKey();
            }

            $walletTransferPaginate = Remit::walletTransfer()->list($inputs);

            return new WalletTransferCollection($walletTransferPaginate);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Create a new *WalletTransfer* resource in storage.
     *
     * @lrd:end
     *
     * @throws StoreOperationException
     */
    public function store(StoreWalletTransferRequest $request): JsonResponse
    {
        $inputs = $request->validated();

        $inputs['user_id'] = ($request->filled('user_id')) ? $request->input('user_id') : $request->user('sanctum')->getKey();

        try {

            $walletTransfer = Remit::walletTransfer()->create($inputs);

            return response()->created([
                'message' => __('core::messages.transaction.request_created', ['service' => 'Wallet Transfer']),
                'id' => $walletTransfer->getKey(),
            ]);

        } catch (Exception $exception) {
            Transaction::orderQueue()->removeFromQueueUserWise($inputs['user_id']);

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Update a specified *WalletTransfer* resource using id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     * @throws UpdateOperationException
     */
    public function update(UpdateWalletTransferRequest $request, string|int $id): JsonResponse
    {
        try {

            $walletTransfer = Remit::walletTransfer()->find($id);

            if (! $walletTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.wallet_transfer_model'), $id);
            }

            $inputs = $request->validated();

            if (! Remit::walletTransfer()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.remit.wallet_transfer_model'), $id);
            }

            return response()->updated(__('core::messages.resource.updated', ['model' => 'Wallet Transfer']));

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Return a specified *WalletTransfer* resource found by id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): WalletTransferResource|JsonResponse
    {
        try {

            $walletTransfer = Remit::walletTransfer()->find($id);

            if (! $walletTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.wallet_transfer_model'), $id);
            }

            return new WalletTransferResource($walletTransfer);

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *WalletTransfer* resource using id.
     *
     * @lrd:end
     *
     * @return JsonResponse
     *
     * @throws ModelNotFoundException
     * @throws DeleteOperationException
     */
    public function destroy(string|int $id)
    {
        try {

            $walletTransfer = Remit::walletTransfer()->find($id);

            if (! $walletTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.wallet_transfer_model'), $id);
            }

            if (! Remit::walletTransfer()->destroy($id)) {

                throw (new DeleteOperationException)->setModel(config('fintech.remit.wallet_transfer_model'), $id);
            }

            return response()->deleted(__('core::messages.resource.deleted', ['model' => 'Wallet Transfer']));

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Restore the specified *WalletTransfer* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     *
     * @lrd:end
     *
     * @return JsonResponse
     */
    public function restore(string|int $id)
    {
        try {

            $walletTransfer = Remit::walletTransfer()->find($id, true);

            if (! $walletTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.wallet_transfer_model'), $id);
            }

            if (! Remit::walletTransfer()->restore($id)) {

                throw (new RestoreOperationException)->setModel(config('fintech.remit.wallet_transfer_model'), $id);
            }

            return response()->restored(__('core::messages.resource.restored', ['model' => 'Wallet Transfer']));

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *WalletTransfer* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function export(IndexWalletTransferRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletTransferPaginate = Remit::walletTransfer()->export($inputs);

            return response()->exported(__('core::messages.resource.exported', ['model' => 'Wallet Transfer']));

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *WalletTransfer* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @return WalletTransferCollection|JsonResponse
     */
    public function import(ImportWalletTransferRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletTransferPaginate = Remit::walletTransfer()->list($inputs);

            return new WalletTransferCollection($walletTransferPaginate);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Create a new *WalletTransfer* resource in storage.
     *
     * @lrd:end
     *
     * @throws StoreOperationException
     */
    public function storeWithoutInsufficientBalance(StoreWalletTransferRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $inputs = $request->validated();

            if ($request->input('user_id') > 0) {
                $user_id = $request->input('user_id');
            }
            $depositor = $request->user('sanctum');
            if (Transaction::orderQueue()->addToQueueUserWise(($user_id ?? $depositor->getKey())) > 0) {

                $depositAccount = Transaction::userAccount()->findWhere(['user_id' => $user_id ?? $depositor->getKey(), 'country_id' => $request->input('source_country_id', $depositor->profile?->country_id)]);

                if (! $depositAccount) {
                    throw new Exception("User don't have account deposit balance");
                }

                $masterUser = Auth::user()->findWhere(['role_name' => SystemRole::MasterUser->value, 'country_id' => $request->input('source_country_id', $depositor->profile?->country_id)]);

                if (! $masterUser) {
                    throw new Exception('Master User Account not found for '.$request->input('source_country_id', $depositor->profile?->country_id).' country');
                }

                //set pre defined conditions of deposit
                $inputs['transaction_form_id'] = Transaction::transactionForm()->findWhere(['code' => 'wallet_transfer'])->getKey();
                $inputs['user_id'] = $user_id ?? $depositor->getKey();
                $delayCheck = Transaction::order()->transactionDelayCheck($inputs);
                if ($delayCheck['countValue'] > 0) {
                    throw new Exception('Your Request For This Amount Is Already Submitted. Please Wait For Update');
                }
                $inputs['sender_receiver_id'] = $masterUser->getKey();
                $inputs['is_refunded'] = false;
                $inputs['status'] = OrderStatus::Successful->value;
                $inputs['risk'] = RiskProfile::Low->value;
                $inputs['reverse'] = true;
                $inputs['order_data']['currency_convert_rate'] = Business::currencyRate()->convert($inputs);
                unset($inputs['reverse']);
                $inputs['converted_amount'] = $inputs['order_data']['currency_convert_rate']['converted'];
                $inputs['converted_currency'] = $inputs['order_data']['currency_convert_rate']['output'];
                $inputs['order_data']['created_by'] = $depositor->name;
                $inputs['order_data']['created_by_mobile_number'] = $depositor->mobile;
                $inputs['order_data']['created_at'] = now();
                $inputs['order_data']['master_user_name'] = $masterUser['name'];
                //$inputs['order_data']['operator_short_code'] = $request->input('operator_short_code', null);
                $inputs['order_data']['assign_order'] = 'no';
                $inputs['order_data']['system_notification_variable_success'] = 'wallet_transfer_success';
                $inputs['order_data']['system_notification_variable_failed'] = 'wallet_transfer_failed';
                unset($inputs['pin'], $inputs['password']);

                $walletTransfer = Remit::walletTransfer()->create($inputs);

                if (! $walletTransfer) {
                    throw (new StoreOperationException)->setModel(config('fintech.remit.wallet_transfer_model'));
                }

                $order_data = $walletTransfer->order_data;
                $order_data['purchase_number'] = entry_number($walletTransfer->getKey(), $walletTransfer->sourceCountry->iso3, OrderStatus::Successful->value);
                $order_data['service_stat_data'] = Business::serviceStat()->serviceStateData($walletTransfer);
                $service = Business::service()->find($inputs['service_id']);
                $order_data['service_slug'] = $service->service_slug;
                $order_data['service_name'] = $service->service_name;
                $order_data['user_name'] = $walletTransfer->user->name;
                $walletTransfer->order_data = $order_data;
                $userUpdatedBalance = Remit::walletTransfer()->debitTransaction($walletTransfer);
                $depositedAccount = Transaction::userAccount()->findWhere(['user_id' => $depositor->getKey(), 'country_id' => $walletTransfer->source_country_id]);
                //update User Account
                $depositedUpdatedAccount = $depositedAccount->toArray();
                $depositedUpdatedAccount['user_account_data']['spent_amount'] = (float) $depositedUpdatedAccount['user_account_data']['spent_amount'] + (float) $userUpdatedBalance['spent_amount'];
                $depositedUpdatedAccount['user_account_data']['available_amount'] = (float) $userUpdatedBalance['current_amount'];

                if (((float) $depositedUpdatedAccount['user_account_data']['available_amount']) < ((float) config('fintech.transaction.minimum_balance'))) {
                    throw new Exception(__('Insufficient balance!', [
                        'previous_amount' => ((float) $depositedUpdatedAccount['user_account_data']['available_amount']),
                        'current_amount' => ((float) $userUpdatedBalance['spent_amount']),
                    ]));
                }

                $order_data['previous_amount'] = (float) $depositedAccount->user_account_data['available_amount'];
                $order_data['current_amount'] = ((float) $order_data['previous_amount'] + (float) $inputs['converted_currency']);
                if (! Transaction::userAccount()->update($depositedAccount->getKey(), $depositedUpdatedAccount)) {
                    throw new Exception(__('User Account Balance does not update', [
                        'current_status' => $walletTransfer->currentStatus(),
                        'target_status' => OrderStatus::Success->value,
                    ]));
                }
                //TODO ALL Beneficiary Data with bank and branch data
                $beneficiaryData = Banco::beneficiary()->manageBeneficiaryData($order_data);
                $order_data['beneficiary_data'] = $beneficiaryData;

                Remit::walletTransfer()->update($walletTransfer->getKey(), ['order_data' => $order_data, 'order_number' => $order_data['purchase_number']]);
                Transaction::orderQueue()->removeFromQueueUserWise($user_id ?? $depositor->getKey());
                DB::commit();

                return response()->created([
                    'message' => __('core::messages.resource.created', ['model' => 'Wallet Transfer']),
                    'id' => $walletTransfer->id,
                ]);
            } else {
                throw new Exception('Your another order is in process...!');
            }
        } catch (Exception $exception) {

            Transaction::orderQueue()->removeFromQueueUserWise($user_id ?? $depositor->getKey());
            DB::rollBack();

            return response()->failed($exception);
        }
    }
}

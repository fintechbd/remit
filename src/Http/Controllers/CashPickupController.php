<?php

namespace Fintech\Remit\Http\Controllers;

use Exception;
use Fintech\Auth\Facades\Auth;
use Fintech\Core\Enums\Auth\RiskProfile;
use Fintech\Core\Enums\Auth\SystemRole;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Remit\Http\Requests\ImportCashPickupRequest;
use Fintech\Remit\Http\Requests\IndexCashPickupRequest;
use Fintech\Remit\Http\Requests\StoreCashPickupRequest;
use Fintech\Remit\Http\Requests\UpdateCashPickupRequest;
use Fintech\Remit\Http\Resources\CashPickupCollection;
use Fintech\Remit\Http\Resources\CashPickupResource;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Class CashPickupController
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to CashPickup
 *
 * @lrd:end
 */
class CashPickupController extends Controller
{
    /**
     * @lrd:start
     * Return a listing of the *CashPickup* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     *
     * @lrd:end
     */
    public function index(IndexCashPickupRequest $request): CashPickupCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $inputs['transaction_form_id'] = transaction()->transactionForm()->findWhere(['code' => 'money_transfer'])->getKey();

            if ($request->isAgent()) {
                $inputs['creator_id'] = $request->user('sanctum')->getKey();
            }

            $cashPickupPaginate = remit()->cashPickup()->list($inputs);

            return new CashPickupCollection($cashPickupPaginate);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Create a new *CashPickup* resource in storage.
     *
     * @lrd:end
     *
     * @throws StoreOperationException
     */
    public function store(StoreCashPickupRequest $request): JsonResponse
    {
        $inputs = $request->validated();

        $inputs['user_id'] = ($request->filled('user_id')) ? $request->input('user_id') : $request->user('sanctum')->getKey();

        try {

            $cashPickup = remit()->cashPickup()->create($inputs);

            return response()->created([
                'message' => __('core::messages.transaction.request_created', ['service' => 'Cash Pickup']),
                'id' => $cashPickup->getKey(),
                'order_number' => $cashPickup->order_number ?? $cashPickup->order_data['purchase_number'],
            ]);

        } catch (Exception $exception) {
            transaction()->orderQueue()->removeFromQueueUserWise($inputs['user_id']);

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Update a specified *CashPickup* resource using id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     * @throws UpdateOperationException
     */
    public function update(UpdateCashPickupRequest $request, string|int $id): JsonResponse
    {
        try {

            $cashPickup = remit()->cashPickup()->find($id);

            if (! $cashPickup) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            $inputs = $request->validated();

            if (! remit()->cashPickup()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            return response()->updated(__('core::messages.resource.updated', ['model' => 'Cash Pickup']));

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Return a specified *CashPickup* resource found by id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): CashPickupResource|JsonResponse
    {
        try {

            $cashPickup = remit()->cashPickup()->find($id);

            if (! $cashPickup) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            return new CashPickupResource($cashPickup);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *CashPickup* resource using id.
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

            $cashPickup = remit()->cashPickup()->find($id);

            if (! $cashPickup) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            if (! remit()->cashPickup()->destroy($id)) {

                throw (new DeleteOperationException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            return response()->deleted(__('core::messages.resource.deleted', ['model' => 'Cash Pickup']));

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Restore the specified *CashPickup* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     *
     * @lrd:end
     *
     * @return JsonResponse
     */
    public function restore(string|int $id)
    {
        try {

            $cashPickup = remit()->cashPickup()->find($id, true);

            if (! $cashPickup) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            if (! remit()->cashPickup()->restore($id)) {

                throw (new RestoreOperationException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            return response()->restored(__('core::messages.resource.restored', ['model' => 'Cash Pickup']));

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Create an exportable list of the *CashPickup* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function export(IndexCashPickupRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $cashPickupPaginate = remit()->cashPickup()->export($inputs);

            return response()->exported(__('core::messages.resource.exported', ['model' => 'Cash Pickup']));

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Create an exportable list of the *CashPickup* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @return CashPickupCollection|JsonResponse
     */
    public function import(ImportCashPickupRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $cashPickupPaginate = remit()->cashPickup()->list($inputs);

            return new CashPickupCollection($cashPickupPaginate);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Create a new *CashPickup* resource in storage.
     *
     * @lrd:end
     *
     * @throws StoreOperationException
     */
    public function storeWithoutInsufficientBalance(StoreCashPickupRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $inputs = $request->validated();

            if ($request->input('user_id') > 0) {
                $user_id = $request->input('user_id');
            }
            $depositor = $request->user('sanctum');
            if (Transaction::orderQueue()->addToQueueUserWise(($user_id ?? $depositor->getKey())) > 0) {
                $depositAccount = transaction()->userAccount()->findWhere(['user_id' => $user_id ?? $depositor->getKey(), 'country_id' => $request->input('source_country_id', $depositor->profile?->country_id)]);

                if (! $depositAccount) {
                    throw new Exception("User don't have account deposit balance");
                }

                $masterUser = Auth::user()->findWhere(['role_name' => SystemRole::MasterUser->value, 'country_id' => $request->input('source_country_id', $depositor->profile?->country_id)]);

                if (! $masterUser) {
                    throw new Exception('Master User Account not found for '.$request->input('source_country_id', $depositor->profile?->country_id).' country');
                }

                // set pre defined conditions of deposit
                $inputs['transaction_form_id'] = transaction()->transactionForm()->findWhere(['code' => 'money_transfer'])->getKey();
                $inputs['user_id'] = $user_id ?? $depositor->getKey();
                $delayCheck = transaction()->order()->transactionDelayCheck($inputs);
                if ($delayCheck['countValue'] > 0) {
                    throw new Exception('Your Request For This Amount Is Already Submitted. Please Wait For Update');
                }
                $inputs['sender_receiver_id'] = $masterUser->getKey();
                $inputs['is_refunded'] = false;
                $inputs['status'] = OrderStatus::Successful->value;
                $inputs['risk'] = RiskProfile::Low->value;
                $inputs['reverse'] = true;
                $inputs['order_data']['currency_convert_rate'] = business()->currencyRate()->convert($inputs);
                unset($inputs['reverse']);
                $inputs['converted_amount'] = $inputs['order_data']['currency_convert_rate']['converted'];
                $inputs['converted_currency'] = $inputs['order_data']['currency_convert_rate']['output'];
                $inputs['order_data']['created_by'] = $depositor->name;
                $inputs['order_data']['created_by_mobile_number'] = $depositor->mobile;
                $inputs['order_data']['created_at'] = now();
                $inputs['order_data']['master_user_name'] = $masterUser['name'];
                // $inputs['order_data']['operator_short_code'] = $request->input('operator_short_code', null);
                $inputs['order_data']['assign_order'] = 'no';
                $inputs['order_data']['system_notification_variable_success'] = 'cash_pickup_success';
                $inputs['order_data']['system_notification_variable_failed'] = 'cash_pickup_failed';
                unset($inputs['pin'], $inputs['password']);

                $cashPickup = remit()->cashPickup()->create($inputs);

                if (! $cashPickup) {
                    throw (new StoreOperationException)->setModel(config('fintech.remit.cash_pickup_model'));
                }

                $order_data = $cashPickup->order_data;
                $order_data['purchase_number'] = entry_number($cashPickup->getKey(), $cashPickup->sourceCountry->iso3, OrderStatus::Successful->value);
                $service = business()->service()->find($inputs['service_id']);
                $order_data['service_slug'] = $service->service_slug;
                $order_data['service_name'] = $service->service_name;
                $order_data['service_stat_data'] = business()->serviceStat()->serviceStateData($cashPickup);
                $order_data['user_name'] = $cashPickup->user->name;
                $cashPickup->order_data = $order_data;
                $userUpdatedBalance = remit()->cashPickup()->debitTransaction($cashPickup);
                $depositedAccount = transaction()->userAccount()->findWhere(['user_id' => $depositor->getKey(), 'country_id' => $cashPickup->source_country_id]);
                // update User Account
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
                if (! transaction()->userAccount()->update($depositedAccount->getKey(), $depositedUpdatedAccount)) {
                    throw new Exception(__('User Account Balance does not update', [
                        'current_status' => $cashPickup->currentStatus(),
                        'target_status' => OrderStatus::Success->value,
                    ]));
                }
                // TODO ALL Beneficiary Data with bank and branch data
                $beneficiaryData = banco()->beneficiary()->manageBeneficiaryData($order_data);
                $order_data['beneficiary_data'] = $beneficiaryData;

                remit()->bankTransfer()->update($cashPickup->getKey(), ['order_data' => $order_data, 'order_number' => $order_data['purchase_number']]);
                transaction()->orderQueue()->removeFromQueueUserWise($user_id ?? $depositor->getKey());
                DB::commit();

                return response()->created([
                    'message' => __('core::messages.resource.created', ['model' => 'Cash Pickup']),
                    'id' => $cashPickup->id,
                ]);
            } else {
                throw new Exception('Your another order is in process...!');
            }
        } catch (Exception $exception) {

            transaction()->orderQueue()->removeFromQueueUserWise($user_id ?? $depositor->getKey());
            DB::rollBack();

            return response()->failed($exception);
        }
    }
}

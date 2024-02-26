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
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Remit\Events\RemitTransferRequested;
use Fintech\Remit\Facades\Remit;
use Fintech\Remit\Http\Requests\ImportBankTransferRequest;
use Fintech\Remit\Http\Requests\IndexBankTransferRequest;
use Fintech\Remit\Http\Requests\StoreBankTransferRequest;
use Fintech\Remit\Http\Requests\UpdateBankTransferRequest;
use Fintech\Remit\Http\Resources\BankTransferCollection;
use Fintech\Remit\Http\Resources\BankTransferResource;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Class BankTransferController
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to BankTransfer
 *
 * @lrd:end
 */
class BankTransferController extends Controller
{
    use ApiResponseTrait;

    /**
     * @lrd:start
     * Return a listing of the *BankTransfer* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     *
     * @lrd:end
     */
    public function index(IndexBankTransferRequest $request): BankTransferCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $inputs['transaction_form_id'] = Transaction::transactionForm()->list(['code' => 'money_transfer'])->first()->getKey();
            $bankTransferPaginate = Remit::bankTransfer()->list($inputs);

            return new BankTransferCollection($bankTransferPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a new *BankTransfer* resource in storage.
     *
     * @lrd:end
     */
    public function store(StoreBankTransferRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $inputs = $request->validated();
            if ($request->input('user_id') > 0) {
                $user_id = $request->input('user_id');
            }
            $depositor = $request->user('sanctum');
            if (Transaction::orderQueue()->addToQueueUserWise(($user_id ?? $depositor->getKey())) > 0) {
                $depositAccount = Transaction::userAccount()->list([
                    'user_id' => $user_id ?? $depositor->getKey(),
                    'country_id' => $request->input('source_country_id', $depositor->profile?->country_id),
                ])->first();

                if (!$depositAccount) {
                    throw new Exception("User don't have account deposit balance");
                }

                $masterUser = Auth::user()->list([
                    'role_name' => SystemRole::MasterUser->value,
                    'country_id' => $request->input('source_country_id', $depositor->profile?->country_id),
                ])->first();

                if (!$masterUser) {
                    throw new Exception('Master User Account not found for ' . $request->input('source_country_id', $depositor->profile?->country_id) . ' country');
                }

                //set pre defined conditions of deposit
                $inputs['transaction_form_id'] = Transaction::transactionForm()->list(['code' => 'money_transfer'])->first()->getKey();
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
                $inputs['order_data']['system_notification_variable_success'] = 'bank_transfer_success';
                $inputs['order_data']['system_notification_variable_failed'] = 'bank_transfer_failed';
                unset($inputs['pin'], $inputs['password']);

                $bankTransfer = Remit::bankTransfer()->create($inputs);

                if (!$bankTransfer) {
                    throw (new StoreOperationException)->setModel(config('fintech.remit.bank_transfer_model'));
                }
                $order_data = $bankTransfer->order_data;
                $order_data['purchase_number'] = entry_number($bankTransfer->getKey(), $bankTransfer->sourceCountry->iso3, OrderStatus::Successful->value);
                $order_data['service_stat_data'] = Business::serviceStat()->serviceStateData($bankTransfer);
                $order_data['user_name'] = $bankTransfer->user->name;
                $bankTransfer->order_data = $order_data;
                $userUpdatedBalance = Remit::bankTransfer()->debitTransaction($bankTransfer);
                $depositedAccount = Transaction::userAccount()->list([
                    'user_id' => $depositor->getKey(),
                    'country_id' => $bankTransfer->source_country_id,
                ])->first();
                //update User Account
                $depositedUpdatedAccount = $depositedAccount->toArray();
                $depositedUpdatedAccount['user_account_data']['spent_amount'] = (float)$depositedUpdatedAccount['user_account_data']['spent_amount'] + (float)$userUpdatedBalance['spent_amount'];
                $depositedUpdatedAccount['user_account_data']['available_amount'] = (float)$userUpdatedBalance['current_amount'];

                $order_data['order_data']['previous_amount'] = (float)$depositedAccount->user_account_data['available_amount'];
                $order_data['order_data']['current_amount'] = ((float)$order_data['order_data']['previous_amount'] + (float)$inputs['converted_currency']);
                if (!Transaction::userAccount()->update($depositedAccount->getKey(), $depositedUpdatedAccount)) {
                    throw new Exception(__('User Account Balance does not update', [
                        'current_status' => $bankTransfer->currentStatus(),
                        'target_status' => OrderStatus::Success->value,
                    ]));
                }
                //TODO ALL Beneficiary Data with bank and branch data
                $beneficiaryData = Banco::beneficiary()->manageBeneficiaryData($order_data);
                $order_data['order_data']['beneficiary_data'] = $beneficiaryData;

                Remit::bankTransfer()->update($bankTransfer->getKey(), ['order_data' => $order_data, 'order_number' => $order_data['purchase_number']]);
                Transaction::orderQueue()->removeFromQueueUserWise($user_id ?? $depositor->getKey());

                event(new RemitTransferRequested('bank_deposit', $bankTransfer));

                DB::commit();

                return $this->created([
                    'message' => __('core::messages.resource.created', ['model' => 'Bank Transfer']),
                    'id' => $bankTransfer->id,
                ]);
            } else {
                throw new Exception('Your another order is in process...!');
            }
        } catch (Exception $exception) {

            DB::rollBack();
            Transaction::orderQueue()->removeFromQueueUserWise($user_id ?? $depositor->getKey());

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Update a specified *BankTransfer* resource using id.
     *
     * @lrd:end
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
     * Return a specified *BankTransfer* resource found by id.
     *
     * @lrd:end
     *
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
     * Soft delete a specified *BankTransfer* resource using id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     * @throws DeleteOperationException
     */
    public function destroy(string|int $id): JsonResponse
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
     * Create an exportable list of the *BankTransfer* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
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
     * Create an exportable list of the *BankTransfer* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function import(ImportBankTransferRequest $request): JsonResponse|BankTransferCollection
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
     * Assign vendor to a specified *BankTransfer* resource from systems.
     *
     * @lrd:end
     */
    public function fetchAssignableVendors(string|int $id): JsonResponse
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
     * Restore the specified *BankTransfer* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     *
     * @lrd:end
     */
    public function restore(string|int $id): JsonResponse
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
     * Assign vendor to a specified *BankTransfer* resource from systems.
     *
     * @lrd:end
     */
    public function assignVendor(string|int $id): JsonResponse
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
}

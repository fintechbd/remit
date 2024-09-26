<?php

namespace Fintech\Remit\Services;

use Fintech\Auth\Facades\Auth;
use Fintech\Banco\Facades\Banco;
use Fintech\Business\Facades\Business;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Auth\RiskProfile;
use Fintech\Core\Enums\Auth\SystemRole;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Enums\Transaction\OrderType;
use Fintech\Core\Exceptions\Transaction\CurrencyUnavailableException;
use Fintech\Core\Exceptions\Transaction\MasterCurrencyUnavailableException;
use Fintech\Core\Exceptions\Transaction\RequestAmountExistsException;
use Fintech\MetaData\Facades\MetaData;
use Fintech\Remit\Interfaces\BankTransferRepository;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

/**
 * Class BankTransferService
 *
 * @property BankTransferRepository $bankTransferRepository
 */
class BankTransferService
{
    use \Fintech\Core\Traits\HasFindWhereSearch;

    /**
     * BankTransferService constructor.
     */
    public function __construct(BankTransferRepository $bankTransferRepository)
    {
        $this->bankTransferRepository = $bankTransferRepository;
    }

    public function find($id, bool $onlyTrashed = false): ?BaseModel
    {
        return $this->bankTransferRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = []): ?BaseModel
    {
        return $this->bankTransferRepository->update($id, $inputs);
    }

    /**
     * @return mixed
     */
    public function destroy($id)
    {
        return $this->bankTransferRepository->delete($id);
    }

    /**
     * @return mixed
     */
    public function restore($id)
    {
        return $this->bankTransferRepository->restore($id);
    }

    public function export(array $filters): Paginator|Collection
    {
        return $this->bankTransferRepository->list($filters);
    }

    public function list(array $filters = []): Collection|Paginator
    {
        return $this->bankTransferRepository->list($filters);

    }

    public function import(array $filters): ?BaseModel
    {
        return $this->bankTransferRepository->create($filters);
    }

    /**
     * @throws ModelNotFoundException
     * @throws CurrencyUnavailableException
     * @throws MasterCurrencyUnavailableException
     * @throws RequestAmountExistsException
     * @throws \Exception
     */
    public function create(array $inputs = []): ?BaseModel
    {
        $sender = Auth::user()->find($inputs['user_id']);

        if (!$sender) {
            throw (new ModelNotFoundException)->setModel(config('fintech.auth.auth_model'), $inputs['user_id']);
        }

        $role = $sender->roles->first();

        $inputs['source_country_id'] = $inputs['source_country_id'] ?? $sender->profile?->present_country_id;

        $senderAccount = Transaction::userAccount()->findWhere(['user_id' => $sender->getKey(), 'country_id' => $inputs['source_country_id']]);

        if (!$senderAccount) {
            throw new CurrencyUnavailableException($inputs['source_country_id']);
        }

        $masterUser = Auth::user()->findWhere(['role_name' => SystemRole::MasterUser->value, 'country_id' => $inputs['source_country_id']]);

        if (!$masterUser) {
            throw new MasterCurrencyUnavailableException($inputs['source_country_id']);
        }

        $inputs['transaction_form_id'] = Transaction::transactionForm()->findWhere(['code' => 'money_transfer'])->getKey();

        if (Transaction::order()->transactionDelayCheck($inputs)['countValue'] > 0) {
            throw new RequestAmountExistsException;
        }

        $inputs['sender_receiver_id'] = $masterUser->getKey();
        $inputs['is_refunded'] = false;
        $inputs['status'] = OrderStatus::Pending->value;
        $inputs['risk'] = RiskProfile::Low;
        $inputs['order_data']['order_type'] = OrderType::BankTransfer;
        if (!isset($inputs['reverse'])) {
            $inputs['reverse'] = false;
        }
        $inputs['order_data']['currency_convert_rate'] = Business::currencyRate()->convert($inputs);
        unset($inputs['reverse']);
        $inputs['converted_amount'] = $inputs['order_data']['currency_convert_rate']['converted'];
        $inputs['converted_currency'] = $inputs['order_data']['currency_convert_rate']['output'];
        $inputs['order_data']['created_by'] = $sender->name ?? 'N/A';
        $inputs['order_data']['user_name'] = $sender->name ?? 'N/A';
        $inputs['order_data']['created_by_mobile_number'] = $sender->mobile ?? 'N/A';
        $inputs['order_data']['created_by_email'] = $sender->email ?? 'N/A';
        $inputs['order_data']['created_at'] = now();
        $inputs['order_data']['master_user_name'] = $masterUser->name;
        $inputs['order_data']['sending_amount'] = $inputs['converted_amount'];
        $inputs['order_data']['assign_order'] = 'no';
        $inputs['order_data']['system_notification_variable_success'] = 'bank_transfer_success';
        $inputs['order_data']['system_notification_variable_failed'] = 'bank_transfer_failed';
        $inputs['order_data']['purchase_number'] = next_purchase_number(MetaData::country()->find($inputs['source_country_id'])->iso3);
        $inputs['order_number'] = $inputs['order_data']['purchase_number'];
        if ($service = Business::service()->find($inputs['service_id'])) {
            $inputs['order_data']['service_slug'] = $service->service_slug ?? null;
            $inputs['order_data']['service_name'] = $service->service_name ?? null;
            $vendor = $service->serviceVendor;
            $inputs['service_vendor_id'] = $vendor?->getKey() ?? null;
            $inputs['vendor'] = $vendor?->service_vendor_slug ?? null;
        }
        $inputs['timeline'][] = [
            'message' => 'Bank Transfer entry created successfully',
            'flag' => 'create',
            'timestamp' => now(),
        ];
        $inputs['order_data']['beneficiary_data'] = Banco::beneficiary()->manageBeneficiaryData($inputs['order_data']);
        $inputs['order_data']['service_stat_data'] = Business::serviceStat()->cost([...$inputs, 'role_id' => $role->getKey()]);
        $inputs['order_data']['service_stat_data'] = Arr::only(
            $inputs['order_data']['service_stat_data'],
            [
                "charge", "discount", "commission", "charge_refund",
                "discount_refund", "commission_refund", "charge_break_down_id",
                "service_stat_id"
            ]
        );

        DB::beginTransaction();

        if ($bankTransfer = $this->bankTransferRepository->create($inputs)) {
            DB::commit();
            $userUpdatedBalance = $this->debitTransaction($bankTransfer);
            $senderUpdatedAccount = $senderAccount->toArray();
            $senderUpdatedAccount['user_account_data']['spent_amount'] = (float)$senderUpdatedAccount['user_account_data']['spent_amount'] + (float)$userUpdatedBalance['spent_amount'];
            $senderUpdatedAccount['user_account_data']['available_amount'] = (float)$userUpdatedBalance['current_amount'];

            $inputs['order_data']['previous_amount'] = (float)$senderAccount->user_account_data['available_amount'];
            $inputs['order_data']['current_amount'] = ((float)$inputs['order_data']['previous_amount'] + (float)$inputs['converted_currency']);

            $bankTransfer = $this->bankTransferRepository->update($bankTransfer->getKey(), ['order_data' => $inputs['order_data']]);

            if (!Transaction::userAccount()->update($senderAccount->getKey(), $senderUpdatedAccount)) {
                throw new \Exception(__('User Account Balance does not update', [
                    'current_status' => $bankTransfer->currentStatus(),
                    'target_status' => OrderStatus::Success->value,
                ]));
            }
        }

        DB::rollBack();

        return null;
    }

    /**
     * @return int[]
     */
    public function debitTransaction($bankTransfer): array
    {
        $userAccountData = [
            'previous_amount' => null,
            'current_amount' => null,
            'spent_amount' => null,
        ];

        //Collect Current Balance as Previous Balance
        $userAccountData['previous_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $bankTransfer->user_id,
            'converted_currency' => $bankTransfer->converted_currency,
        ]);

        $serviceStatData = $bankTransfer->order_data['service_stat_data'];
        $master_user_name = $bankTransfer->order_data['master_user_name'];
        $user_name = $bankTransfer->order_data['user_name'];

        $amount = $bankTransfer->amount;
        $converted_amount = $bankTransfer->converted_amount;
        $bankTransfer->amount = -$amount;
        $bankTransfer->converted_amount = -$converted_amount;
        $bankTransfer->order_detail_cause_name = 'cash_withdraw';
        $bankTransfer->order_detail_number = $bankTransfer->order_data['purchase_number'];
        $bankTransfer->order_detail_response_id = $bankTransfer->order_data['purchase_number'];
        $bankTransfer->notes = 'Bank Transfer Payment Send to ' . $master_user_name;
        $orderDetailStore = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($bankTransfer));
        $orderDetailStore->order_detail_parent_id = $bankTransfer->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStore->save();
        $orderDetailStore->fresh();
        $orderDetailStoreForMaster = $orderDetailStore->replicate();
        $orderDetailStoreForMaster->user_id = $bankTransfer->sender_receiver_id;
        $orderDetailStoreForMaster->sender_receiver_id = $bankTransfer->user_id;
        $orderDetailStoreForMaster->order_detail_amount = $amount;
        $orderDetailStoreForMaster->converted_amount = $converted_amount;
        $orderDetailStoreForMaster->step = 2;
        $orderDetailStoreForMaster->notes = 'Bank Transfer Payment Receive From' . $user_name;
        $orderDetailStoreForMaster->save();

        //For Charge
        $bankTransfer->amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $bankTransfer->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $bankTransfer->order_detail_cause_name = 'charge';
        $bankTransfer->order_detail_parent_id = $orderDetailStore->getKey();
        $bankTransfer->notes = 'Bank Transfer Charge Send to ' . $master_user_name;
        $bankTransfer->step = 3;
        $bankTransfer->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($bankTransfer));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $bankTransfer->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $bankTransfer->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Bank Transfer Charge Receive from ' . $user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();

        //For Discount
        $bankTransfer->amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $bankTransfer->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $bankTransfer->order_detail_cause_name = 'discount';
        $bankTransfer->notes = 'Bank Transfer Discount form ' . $master_user_name;
        $bankTransfer->step = 5;
        //$data->order_detail_parent_id = $orderDetailStore->getKey();
        //$updateData['order_data']['previous_amount'] = 0;
        $orderDetailStoreForDiscount = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($bankTransfer));
        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForDiscountForMaster->user_id = $bankTransfer->sender_receiver_id;
        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $bankTransfer->user_id;
        $orderDetailStoreForDiscountForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
        $orderDetailStoreForDiscountForMaster->notes = 'Bank Transfer Discount to ' . $user_name;
        $orderDetailStoreForDiscountForMaster->step = 6;
        $orderDetailStoreForDiscountForMaster->save();

        //'Point Transfer Commission Send to ' . $masterUser->name;
        //'Point Transfer Commission Receive from ' . $receiver->name;

        $userAccountData['current_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $bankTransfer->user_id,
            'converted_currency' => $bankTransfer->converted_currency,
        ]);

        $userAccountData['spent_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $bankTransfer->user_id,
            'order_id' => $bankTransfer->getKey(),
            'converted_currency' => $bankTransfer->converted_currency,
        ]);

        return $userAccountData;

    }

    /**
     * @return int[]
     */
    public function creditTransaction($data): array
    {
        $userAccountData = [
            'previous_amount' => null,
            'current_amount' => null,
            'spent_amount' => null,
        ];

        //Collect Current Balance as Previous Balance
        $userAccountData['previous_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $data->user_id,
            'converted_currency' => $data->converted_currency,
        ]);

        $serviceStatData = $data->order_data['service_stat_data'];
        $master_user_name = $data->order_data['master_user_name'];
        $user_name = $data->order_data['user_name'];

        $data->order_detail_cause_name = 'cash_withdraw';
        $data->order_detail_number = $data->order_data['accepted_number'];
        $data->order_detail_response_id = $data->order_data['purchase_number'];
        $data->notes = 'Bank Transfer Refund From ' . $master_user_name;
        $orderDetailStore = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStore->order_detail_parent_id = $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStore->save();
        $orderDetailStore->fresh();
        $amount = $data->amount;
        $converted_amount = $data->converted_amount;
        $orderDetailStoreForMaster = $orderDetailStore->replicate();
        $orderDetailStoreForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForMaster->order_detail_amount = -$amount;
        $orderDetailStoreForMaster->converted_amount = -$converted_amount;
        $orderDetailStoreForMaster->step = 2;
        $orderDetailStoreForMaster->notes = 'Bank Transfer Send to ' . $user_name;
        $orderDetailStoreForMaster->save();

        //For Charge
        $data->amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $data->order_detail_cause_name = 'charge';
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $data->notes = 'Bank Transfer Charge Receive from ' . $master_user_name;
        $data->step = 3;
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Bank Transfer Charge Send to ' . $user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();

        //For Discount
        $data->amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $data->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $data->order_detail_cause_name = 'discount';
        $data->notes = 'Bank Transfer Discount form ' . $master_user_name;
        $data->step = 5;
        //$data->order_detail_parent_id = $orderDetailStore->getKey();
        //$updateData['order_data']['previous_amount'] = 0;
        $orderDetailStoreForDiscount = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForDiscountForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForDiscountForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
        $orderDetailStoreForDiscountForMaster->notes = 'Bank Transfer Discount to ' . $user_name;
        $orderDetailStoreForDiscountForMaster->step = 6;
        $orderDetailStoreForDiscountForMaster->save();

        //'Point Transfer Commission Send to ' . $masterUser->name;
        //'Point Transfer Commission Receive from ' . $receiver->name;

        $userAccountData['current_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $data->user_id,
            'converted_currency' => $data->converted_currency,
        ]);

        $userAccountData['spent_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $data->user_id,
            'order_id' => $data->getKey(),
            'converted_currency' => $data->converted_currency,
        ]);

        return $userAccountData;

    }
}

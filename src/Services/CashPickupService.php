<?php

namespace Fintech\Remit\Services;

use Fintech\Auth\Facades\Auth;
use Fintech\Business\Exceptions\BusinessException;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Auth\RiskProfile;
use Fintech\Core\Enums\Auth\SystemRole;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Enums\Transaction\OrderType;
use Fintech\Core\Exceptions\Transaction\CurrencyUnavailableException;
use Fintech\Core\Exceptions\Transaction\InsufficientBalanceException;
use Fintech\Core\Exceptions\Transaction\MasterCurrencyUnavailableException;
use Fintech\Core\Exceptions\Transaction\OrderRequestFailedException;
use Fintech\Core\Exceptions\Transaction\RequestAmountExistsException;
use Fintech\Core\Exceptions\Transaction\RequestOrderExistsException;
use Fintech\MetaData\Facades\MetaData;
use Fintech\Remit\Events\CashPickupRequested;
use Fintech\Remit\Interfaces\CashPickupRepository;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class CashPickupService
 *
 * @property CashPickupRepository $cashPickupRepository
 */
class CashPickupService
{
    use \Fintech\Core\Traits\HasFindWhereSearch;

    /**
     * CashPickupService constructor.
     */
    public function __construct(public CashPickupRepository $cashPickupRepository) {}

    public function find($id, bool $onlyTrashed = false): ?BaseModel
    {
        return $this->cashPickupRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = []): ?BaseModel
    {
        return $this->cashPickupRepository->update($id, $inputs);
    }

    public function destroy($id): mixed
    {
        return $this->cashPickupRepository->delete($id);
    }

    public function restore($id): mixed
    {
        return $this->cashPickupRepository->restore($id);
    }

    public function export(array $filters): Paginator|Collection
    {
        return $this->cashPickupRepository->list($filters);
    }

    public function list(array $filters = []): Collection|Paginator
    {
        return $this->cashPickupRepository->list($filters);

    }

    public function import(array $filters): ?BaseModel
    {
        return $this->cashPickupRepository->create($filters);
    }

    /**
     * @throws BusinessException
     * @throws RequestAmountExistsException
     * @throws CurrencyUnavailableException
     * @throws RequestOrderExistsException
     * @throws InsufficientBalanceException
     * @throws OrderRequestFailedException
     * @throws MasterCurrencyUnavailableException
     */
    public function create(array $inputs = []): ?BaseModel
    {
        $allowInsufficientBalance = $inputs['allow_insufficient_balance'] ?? false;

        unset($inputs['allow_insufficient_balance']);

        $sender = Auth::user()->find($inputs['user_id']);

        if (! $sender) {
            throw (new ModelNotFoundException)->setModel(config('fintech.auth.auth_model'), $inputs['user_id']);
        }

        if (Transaction::orderQueue()->addToQueueUserWise($inputs['user_id']) == 0) {
            throw new RequestOrderExistsException;
        }

        $inputs['order_data']['order_type'] = OrderType::CashPickup;
        $inputs['description'] = 'Cash Pickup';
        $inputs['source_country_id'] = $inputs['source_country_id'] ?? $sender->profile?->present_country_id;

        $senderAccount = transaction()->userAccount()->findWhere(['user_id' => $sender->getKey(), 'country_id' => $inputs['source_country_id']]);

        if (! $senderAccount) {
            throw new CurrencyUnavailableException($inputs['source_country_id']);
        }

        $masterUser = Auth::user()->findWhere(['role_name' => SystemRole::MasterUser->value, 'country_id' => $inputs['source_country_id']]);

        if (! $masterUser) {
            throw new MasterCurrencyUnavailableException($inputs['source_country_id']);
        }

        $inputs['transaction_form_id'] = transaction()->transactionForm()->findWhere(['code' => 'money_transfer'])->getKey();

        if (Transaction::order()->transactionDelayCheck($inputs)['countValue'] > 0) {
            throw new RequestAmountExistsException;
        }

        $role = $sender->roles?->first() ?? null;
        $inputs['order_data']['role_id'] = $role->id;
        $inputs['order_data']['is_reload'] = false;
        $inputs['order_data']['is_reverse'] = $inputs['reverse'] ?? false;
        $inputs['sender_receiver_id'] = $masterUser->getKey();
        $inputs['is_refunded'] = false;
        $inputs['status'] = ($allowInsufficientBalance) ? OrderStatus::PaymentPending : OrderStatus::Pending;
        $inputs['risk'] = $sender->risk_profile ?? RiskProfile::Low;
        $currencyConversion = business()->currencyRate()->convert([
            'role_id' => $inputs['order_data']['role_id'],
            'reverse' => $inputs['order_data']['is_reverse'],
            'source_country_id' => $inputs['source_country_id'],
            'destination_country_id' => $inputs['destination_country_id'],
            'amount' => $inputs['amount'],
            'service_id' => $inputs['service_id'],
        ]);
        if ($inputs['order_data']['is_reverse']) {
            $inputs['amount'] = $currencyConversion['converted'];
            $inputs['converted_amount'] = $currencyConversion['amount'];
        } else {
            $inputs['amount'] = $currencyConversion['amount'];
            $inputs['converted_amount'] = $currencyConversion['converted'];
        }
        $inputs['order_data']['currency_convert_rate'] = $currencyConversion;
        unset($inputs['reverse']);
        $inputs['order_data']['allow_insufficient_balance'] = $allowInsufficientBalance;
        $inputs['order_data']['created_by'] = $sender->name ?? 'N/A';
        $inputs['order_data']['user_name'] = $sender->name ?? 'N/A';
        $inputs['order_data']['created_by_mobile_number'] = $sender->mobile ?? 'N/A';
        $inputs['order_data']['created_by_email'] = $sender->email ?? 'N/A';
        $inputs['order_data']['created_at'] = now();
        $inputs['order_data']['master_user_name'] = $masterUser->name;
        $inputs['order_data']['sending_amount'] = $inputs['converted_amount'];
        $inputs['order_data']['assign_order'] = 'no';
        $inputs['order_data']['system_notification_variable_success'] = 'cash_pickup_success';
        $inputs['order_data']['system_notification_variable_failed'] = 'cash_pickup_failed';
        $inputs['order_data']['serving_country_id'] = $inputs['source_country_id'];
        $inputs['order_data']['receiving_country_id'] = $inputs['destination_country_id'];
        $inputs['order_data']['purchase_number'] = next_purchase_number(MetaData::country()->find($inputs['source_country_id'])->iso3);
        $inputs['order_number'] = $inputs['order_data']['purchase_number'];
        $service = business()->service()->find($inputs['service_id']);
        $inputs['order_data']['service_slug'] = $service->service_slug ?? null;
        $inputs['order_data']['service_name'] = $service->service_name ?? null;
        $vendor = $service->serviceVendor;
        $inputs['service_vendor_id'] = $vendor?->getKey() ?? null;
        $inputs['vendor'] = $vendor?->service_vendor_slug ?? null;
        $inputs['timeline'][] = [
            'message' => "Cash Pickup ($service->service_name) entry created successfully",
            'flag' => 'create',
            'timestamp' => now(),
        ];
        $inputs['order_data']['beneficiary_data'] = banco()->beneficiary()->manageBeneficiaryData([...$inputs['order_data'], 'source_country_id' => $inputs['source_country_id']]);
        $inputs['order_data']['service_stat_data'] = business()->serviceStat()->serviceStateData([
            'role_id' => $inputs['order_data']['role_id'],
            'reverse' => false,
            'source_country_id' => $inputs['source_country_id'],
            'destination_country_id' => $inputs['destination_country_id'],
            'amount' => $inputs['amount'],
            'service_id' => $inputs['service_id'],
        ]);

        if (! $allowInsufficientBalance) {
            if ((float) $inputs['order_data']['service_stat_data']['total_amount'] > (float) $senderAccount->user_account_data['available_amount']) {
                throw new InsufficientBalanceException($senderAccount->user_account_data['currency']);
            }
        }

        DB::beginTransaction();
        try {
            $cashPickup = $this->cashPickupRepository->create($inputs);
            DB::commit();
            $accounting = transaction()->accounting($cashPickup);

            $accounting->debitTransaction();

            if (! $allowInsufficientBalance) {
                $accounting->debitBalanceFromUserAccount();
            }

            transaction()->orderQueue()->removeFromQueueUserWise($inputs['user_id']);

            event(new CashPickupRequested($cashPickup));

            return $cashPickup;

        } catch (\Exception $exception) {
            DB::rollBack();
            transaction()->orderQueue()->removeFromQueueUserWise($inputs['user_id']);
            throw new OrderRequestFailedException(OrderType::CashPickup->value, 0, $exception);
        }
    }

    /**
     * @return int[]
     */
    public function debitTransaction($data): array
    {
        $userAccountData = [
            'previous_amount' => null,
            'current_amount' => null,
            'spent_amount' => null,
        ];

        // Collect Current Balance as Previous Balance
        $userAccountData['previous_amount'] = transaction()->orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $data->user_id,
            'order_detail_currency' => $data->currency,
        ]);

        $serviceStatData = $data->order_data['service_stat_data'];
        $master_user_name = $data->order_data['master_user_name'];
        $user_name = $data->order_data['user_name'];

        $amount = $data->amount;
        $converted_amount = $data->converted_amount;
        $data->amount = -$amount;
        $data->converted_amount = -$converted_amount;
        $data->order_detail_cause_name = 'cash_withdraw';
        $data->order_detail_number = $data->order_data['purchase_number'];
        $data->order_detail_response_id = $data->order_data['purchase_number'];
        $data->notes = 'Cash Pickup Payment Send to '.$master_user_name;
        $orderDetailStore = transaction()->orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStore->order_detail_parent_id = $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStore->save();
        $orderDetailStore->fresh();
        $orderDetailStoreForMaster = $orderDetailStore->replicate();
        $orderDetailStoreForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForMaster->order_detail_amount = $amount;
        $orderDetailStoreForMaster->converted_amount = $converted_amount;
        $orderDetailStoreForMaster->step = 2;
        $orderDetailStoreForMaster->notes = 'Cash Pickup Payment Receive From'.$user_name;
        $orderDetailStoreForMaster->save();

        // For Charge
        $data->amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $data->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $data->order_detail_cause_name = 'charge';
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $data->notes = 'Cash Pickup Charge Send to '.$master_user_name;
        $data->step = 3;
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = transaction()->orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Cash Pickup Charge Receive from '.$user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();

        // For Discount
        $data->amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $data->order_detail_cause_name = 'discount';
        $data->notes = 'Cash Pickup Discount form '.$master_user_name;
        $data->step = 5;
        // $data->order_detail_parent_id = $orderDetailStore->getKey();
        // $updateData['order_data']['previous_amount'] = 0;
        $orderDetailStoreForDiscount = transaction()->orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForDiscountForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForDiscountForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
        $orderDetailStoreForDiscountForMaster->notes = 'Cash Pickup Discount to '.$user_name;
        $orderDetailStoreForDiscountForMaster->step = 6;
        $orderDetailStoreForDiscountForMaster->save();

        // 'Point Transfer Commission Send to ' . $masterUser->name;
        // 'Point Transfer Commission Receive from ' . $receiver->name;

        $userAccountData['current_amount'] = transaction()->orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $data->user_id,
            'order_detail_currency' => $data->currency,
        ]);

        $userAccountData['spent_amount'] = transaction()->orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $data->user_id,
            'order_id' => $data->getKey(),
            'order_detail_currency' => $data->currency,
        ]);

        return $userAccountData;

    }

    /**
     * @return int[]
     */
    private function creditTransaction($cashPickup): array
    {
        $userAccountData = [
            'previous_amount' => null,
            'current_amount' => null,
            'spent_amount' => null,
        ];

        // Collect Current Balance as Previous Balance
        $userAccountData['previous_amount'] = transaction()->orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $cashPickup->user_id,
            'order_detail_currency' => $cashPickup->currency,
        ]);

        $serviceStatData = $cashPickup->order_data['service_stat_data'];
        $master_user_name = $cashPickup->order_data['master_user_name'];
        $user_name = $cashPickup->order_data['user_name'];

        $cashPickup->order_detail_cause_name = 'cash_withdraw';
        $cashPickup->order_detail_number = $cashPickup->order_data['accepted_number'];
        $cashPickup->order_detail_response_id = $cashPickup->order_data['purchase_number'];
        $cashPickup->notes = 'Cash Pickup Refund From '.$master_user_name;
        $orderDetailStore = transaction()->orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($cashPickup));
        $orderDetailStore->order_detail_parent_id = $cashPickup->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStore->save();
        $orderDetailStore->fresh();
        $amount = $cashPickup->amount;
        $converted_amount = $cashPickup->converted_amount;
        $orderDetailStoreForMaster = $orderDetailStore->replicate();
        $orderDetailStoreForMaster->user_id = $cashPickup->sender_receiver_id;
        $orderDetailStoreForMaster->sender_receiver_id = $cashPickup->user_id;
        $orderDetailStoreForMaster->order_detail_amount = -$amount;
        $orderDetailStoreForMaster->converted_amount = -$converted_amount;
        $orderDetailStoreForMaster->step = 2;
        $orderDetailStoreForMaster->notes = 'Cash Pickup Send to '.$user_name;
        $orderDetailStoreForMaster->save();

        // For Charge
        $cashPickup->amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $cashPickup->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $cashPickup->order_detail_cause_name = 'charge';
        $cashPickup->order_detail_parent_id = $orderDetailStore->getKey();
        $cashPickup->notes = 'Cash Pickup Charge Receive from '.$master_user_name;
        $cashPickup->step = 3;
        $cashPickup->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = transaction()->orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($cashPickup));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $cashPickup->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $cashPickup->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Cash Pickup Charge Send to '.$user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();

        // For Discount
        $cashPickup->amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $cashPickup->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $cashPickup->order_detail_cause_name = 'discount';
        $cashPickup->notes = 'Cash Pickup Discount form '.$master_user_name;
        $cashPickup->step = 5;
        // $data->order_detail_parent_id = $orderDetailStore->getKey();
        // $updateData['order_data']['previous_amount'] = 0;
        $orderDetailStoreForDiscount = transaction()->orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($cashPickup));
        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForDiscountForMaster->user_id = $cashPickup->sender_receiver_id;
        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $cashPickup->user_id;
        $orderDetailStoreForDiscountForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
        $orderDetailStoreForDiscountForMaster->notes = 'Cash Pickup Discount to '.$user_name;
        $orderDetailStoreForDiscountForMaster->step = 6;
        $orderDetailStoreForDiscountForMaster->save();

        // 'Point Transfer Commission Send to ' . $masterUser->name;
        // 'Point Transfer Commission Receive from ' . $receiver->name;

        $userAccountData['current_amount'] = transaction()->orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $cashPickup->user_id,
            'order_detail_currency' => $cashPickup->currency,
        ]);

        $userAccountData['spent_amount'] = transaction()->orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $cashPickup->user_id,
            'order_id' => $cashPickup->getKey(),
            'order_detail_currency' => $cashPickup->currency,
        ]);

        return $userAccountData;

    }
}

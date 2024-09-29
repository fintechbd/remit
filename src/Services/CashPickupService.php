<?php

namespace Fintech\Remit\Services;

use Fintech\Core\Abstracts\BaseModel;
use Fintech\Remit\Interfaces\CashPickupRepository;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

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
    public function __construct(CashPickupRepository $cashPickupRepository)
    {
        $this->cashPickupRepository = $cashPickupRepository;
    }

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

    public function create(array $inputs = []): ?BaseModel
    {
        return $this->cashPickupRepository->create($inputs);
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

        //Collect Current Balance as Previous Balance
        $userAccountData['previous_amount'] = Transaction::orderDetail()->list([
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
        $orderDetailStore = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
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

        //For Charge
        $data->amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $data->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $data->order_detail_cause_name = 'charge';
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $data->notes = 'Cash Pickup Charge Send to '.$master_user_name;
        $data->step = 3;
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Cash Pickup Charge Receive from '.$user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();

        //For Discount
        $data->amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $data->order_detail_cause_name = 'discount';
        $data->notes = 'Cash Pickup Discount form '.$master_user_name;
        $data->step = 5;
        //$data->order_detail_parent_id = $orderDetailStore->getKey();
        //$updateData['order_data']['previous_amount'] = 0;
        $orderDetailStoreForDiscount = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForDiscountForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForDiscountForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
        $orderDetailStoreForDiscountForMaster->notes = 'Cash Pickup Discount to '.$user_name;
        $orderDetailStoreForDiscountForMaster->step = 6;
        $orderDetailStoreForDiscountForMaster->save();

        //'Point Transfer Commission Send to ' . $masterUser->name;
        //'Point Transfer Commission Receive from ' . $receiver->name;

        $userAccountData['current_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $data->user_id,
            'order_detail_currency' => $data->currency,
        ]);

        $userAccountData['spent_amount'] = Transaction::orderDetail()->list([
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
    public function creditTransaction($cashPickup): array
    {
        $userAccountData = [
            'previous_amount' => null,
            'current_amount' => null,
            'spent_amount' => null,
        ];

        //Collect Current Balance as Previous Balance
        $userAccountData['previous_amount'] = Transaction::orderDetail()->list([
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
        $orderDetailStore = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($cashPickup));
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

        //For Charge
        $cashPickup->amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $cashPickup->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $cashPickup->order_detail_cause_name = 'charge';
        $cashPickup->order_detail_parent_id = $orderDetailStore->getKey();
        $cashPickup->notes = 'Cash Pickup Charge Receive from '.$master_user_name;
        $cashPickup->step = 3;
        $cashPickup->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($cashPickup));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $cashPickup->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $cashPickup->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Cash Pickup Charge Send to '.$user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();

        //For Discount
        $cashPickup->amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $cashPickup->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $cashPickup->order_detail_cause_name = 'discount';
        $cashPickup->notes = 'Cash Pickup Discount form '.$master_user_name;
        $cashPickup->step = 5;
        //$data->order_detail_parent_id = $orderDetailStore->getKey();
        //$updateData['order_data']['previous_amount'] = 0;
        $orderDetailStoreForDiscount = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($cashPickup));
        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForDiscountForMaster->user_id = $cashPickup->sender_receiver_id;
        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $cashPickup->user_id;
        $orderDetailStoreForDiscountForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
        $orderDetailStoreForDiscountForMaster->notes = 'Cash Pickup Discount to '.$user_name;
        $orderDetailStoreForDiscountForMaster->step = 6;
        $orderDetailStoreForDiscountForMaster->save();

        //'Point Transfer Commission Send to ' . $masterUser->name;
        //'Point Transfer Commission Receive from ' . $receiver->name;

        $userAccountData['current_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $cashPickup->user_id,
            'order_detail_currency' => $cashPickup->currency,
        ]);

        $userAccountData['spent_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $cashPickup->user_id,
            'order_id' => $cashPickup->getKey(),
            'order_detail_currency' => $cashPickup->currency,
        ]);

        return $userAccountData;

    }
}

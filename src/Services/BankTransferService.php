<?php

namespace Fintech\Remit\Services;

use Fintech\Core\Abstracts\BaseModel;
use Fintech\Remit\Interfaces\BankTransferRepository;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Class BankTransferService
 *
 * @property BankTransferRepository $bankTransferRepository
 */
class BankTransferService extends \Fintech\Core\Abstracts\Service
{
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

    public function create(array $inputs = []): ?BaseModel
    {
        return $this->bankTransferRepository->create($inputs);
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
            'converted_currency' => $data->converted_currency,
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
        $data->notes = 'Bank Transfer Payment Send to '.$master_user_name;
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
        $orderDetailStoreForMaster->notes = 'Bank Transfer Payment Receive From'.$user_name;
        $orderDetailStoreForMaster->save();

        //For Charge
        $data->amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $data->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $data->order_detail_cause_name = 'charge';
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $data->notes = 'Bank Transfer Charge Send to '.$master_user_name;
        $data->step = 3;
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Bank Transfer Charge Receive from '.$user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();

        //For Discount
        $data->amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $data->order_detail_cause_name = 'discount';
        $data->notes = 'Bank Transfer Discount form '.$master_user_name;
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
        $orderDetailStoreForDiscountForMaster->notes = 'Bank Transfer Discount to '.$user_name;
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
        $data->notes = 'Bank Transfer Refund From '.$master_user_name;
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
        $orderDetailStoreForMaster->notes = 'Bank Transfer Send to '.$user_name;
        $orderDetailStoreForMaster->save();

        //For Charge
        $data->amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $data->order_detail_cause_name = 'charge';
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $data->notes = 'Bank Transfer Charge Receive from '.$master_user_name;
        $data->step = 3;
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Bank Transfer Charge Send to '.$user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();

        //For Discount
        $data->amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $data->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $data->order_detail_cause_name = 'discount';
        $data->notes = 'Bank Transfer Discount form '.$master_user_name;
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
        $orderDetailStoreForDiscountForMaster->notes = 'Bank Transfer Discount to '.$user_name;
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

<?php

namespace Fintech\Remit\Services;

use Fintech\Auth\Facades\Auth;
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
use Fintech\Remit\Events\BankTransferRequested;
use Fintech\Remit\Interfaces\BankTransferRepository;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
    public function __construct(public BankTransferRepository $bankTransferRepository) {}

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
        $allowInsufficientBalance = $inputs['allow_insufficient_balance'] ?? false;

        unset($inputs['allow_insufficient_balance']);

        $sender = Auth::user()->find($inputs['user_id']);

        if (! $sender) {
            throw (new ModelNotFoundException)->setModel(config('fintech.auth.auth_model'), $inputs['user_id']);
        }

        if (Transaction::orderQueue()->addToQueueUserWise($inputs['user_id']) == 0) {
            throw new RequestOrderExistsException;
        }

        $inputs['order_data']['order_type'] = OrderType::BankTransfer;
        $inputs['description'] = 'Bank Transfer';
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
        $inputs['order_data']['system_notification_variable_success'] = 'bank_transfer_success';
        $inputs['order_data']['system_notification_variable_failed'] = 'bank_transfer_failed';
        $inputs['order_data']['purchase_number'] = next_purchase_number(MetaData::country()->find($inputs['source_country_id'])->iso3);
        $inputs['order_number'] = $inputs['order_data']['purchase_number'];
        $service = business()->service()->find($inputs['service_id']);
        $inputs['order_data']['service_slug'] = $service->service_slug ?? null;
        $inputs['order_data']['service_name'] = $service->service_name ?? null;
        $inputs['order_data']['serving_country_id'] = $inputs['source_country_id'];
        $inputs['order_data']['receiving_country_id'] = $inputs['destination_country_id'];
        $vendor = $service->serviceVendor;
        $inputs['service_vendor_id'] = $vendor?->getKey() ?? null;
        $inputs['vendor'] = $vendor?->service_vendor_slug ?? null;
        $inputs['timeline'][] = [
            'message' => "Bank Transfer ($service->service_name) entry created successfully",
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

            $bankTransfer = $this->bankTransferRepository->create($inputs);

            DB::commit();

            $accounting = transaction()->accounting($bankTransfer);

            $accounting->debitTransaction();

            $accounting->debitBalanceFromUserAccount();

            transaction()->orderQueue()->removeFromQueueUserWise($inputs['user_id']);

            event(new BankTransferRequested($bankTransfer));

            $bankTransfer->refresh();

            return $bankTransfer;

        } catch (\Exception $exception) {
            DB::rollBack();
            transaction()->orderQueue()->removeFromQueueUserWise($inputs['user_id']);
            throw new OrderRequestFailedException(OrderType::BankTransfer->value, 0, $exception);
        }
    }
}

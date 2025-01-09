<?php

namespace Fintech\Remit\Listeners;

use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Enums\Transaction\OrderType;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;

class RemitOrderPaymentAccepted implements ShouldQueue
{
    private $order;

    /**
     * Handle the event.
     * @param \Fintech\Reload\Events\DepositAccepted $event
     * @throws \Throwable
     */
    public function handle(object $event): void
    {
        $this->order = Transaction::order()->find($event->deposit->parent_id);

        if ($this->order && in_array($this->order->order_type->value, [OrderType::CashPickup->value, OrderType::WalletTransfer->value, OrderType::BankTransfer->value])) {

            $payoutVendor = $event->deposit->serviceVendor;

            Transaction::order()->update($this->order->getKey(), [
                'status' => OrderStatus::Processing,
                'notes' => "{$this->order->notes}.\nReceived payout confirmation from {$payoutVendor->service_vendor_name} vendor.",
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(object $event, \Throwable $exception): void
    {
        Transaction::order()->update($event->deposit->parent_id, [
            'status' => OrderStatus::AdminVerification->value,
            'notes' => $exception->getMessage(),
        ]);
    }
}

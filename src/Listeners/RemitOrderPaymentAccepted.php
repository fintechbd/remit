<?php

namespace Fintech\Remit\Listeners;

use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;

class RemitOrderPaymentAccepted implements ShouldQueue
{
    private $order;

    /**
     * Handle the event.
     *
     * @param  \Fintech\Reload\Events\DepositAccepted  $event
     *
     * @throws \Throwable
     */
    public function handle(object $event): void
    {
        $this->order = Transaction::order()->find($event->deposit->parent_id);

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

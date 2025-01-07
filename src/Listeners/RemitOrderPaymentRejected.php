<?php

namespace Fintech\Remit\Listeners;

use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;

class RemitOrderPaymentRejected implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @throws \Throwable
     */
    public function handle(object $event): void
    {

    }

    /**
     * Handle a job failure.
     */
    public function failed(object $event, \Throwable $exception): void
    {
        Transaction::order()->update($event->transfer->getKey(), [
            'status' => OrderStatus::AdminVerification->value,
            'notes' => $exception->getMessage(),
        ]);
    }
}

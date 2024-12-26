<?php

namespace Fintech\Remit\Listeners;

use Fintech\Core\Facades\Core;
use Fintech\Reload\Facades\Reload;
use Fintech\Remit\Events\MoneyTransferPayoutRequested;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class MoneyTransferPayoutListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Handle the event.
     */
    public function handle(MoneyTransferPayoutRequested $event): void
    {
        if (Core::packageExists('Reload')) {
            Reload::assignVendor()->requestPayout($event->moneyTransfer);
        }
    }

    /**
     * Handle a failure.
     */
    public function failed(MoneyTransferPayoutRequested $event, \Throwable $exception): void
    {
        Transaction::order()->update($event->moneyTransfer->getKey(), [
            'status' => \Fintech\Core\Enums\Transaction\OrderStatus::AdminVerification->value,
            'notes' => $exception->getMessage(),
        ]);
    }
}

<?php

namespace Fintech\Remit\Listeners;

use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;

class RemitOrderComplianceCheck implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @throws \Throwable
     */
    public function handle(object $event): void
    {
        $order_id = $event->transfer->getKey();

        $timeline = $event->transfer->timeline;
        $timeline[] = [
            'message' => 'Verifying remittance transfer compliance policy.',
            'flag' => 'info',
            'timestamp' => now(),
        ];
        transaction()->order()->update($order_id, ['timeline' => $timeline]);

        \Illuminate\Support\Facades\Bus::batch([
            new \Fintech\Transaction\Jobs\Compliance\LargeCashTransferPolicy($order_id),
            new \Fintech\Transaction\Jobs\Compliance\LargeVirtualCashTransferPolicy($order_id),
            new \Fintech\Transaction\Jobs\Compliance\ElectronicFundTransferPolicy($order_id),
            new \Fintech\Transaction\Jobs\Compliance\SuspiciousTransactionPolicy($order_id),
            new \Fintech\Transaction\Jobs\Compliance\ClientDueDiligencePolicy($order_id),
            new \Fintech\Transaction\Jobs\Compliance\StructuringDetectionPolicy($order_id),
            new \Fintech\Transaction\Jobs\Compliance\HighRiskCountryTransferPolicy($order_id),
            new \Fintech\Transaction\Jobs\Compliance\PEPDetectionPolicy($order_id),
            new \Fintech\Transaction\Jobs\Compliance\HIODetectionPolicy($order_id),
            new \Fintech\Transaction\Jobs\Compliance\AccountVelocityPolicy($order_id),
            new \Fintech\Transaction\Jobs\Compliance\NewProductUsagePolicy($order_id),
            new \Fintech\Transaction\Jobs\Compliance\DormantAccountActivityPolicy($order_id),
            new \Fintech\Transaction\Jobs\Compliance\ThirdPartyTransferPolicy($order_id),
            new \Fintech\Transaction\Jobs\Compliance\VirtualCurrencyTravelPolicy($order_id),
        ])
            ->then(function (Batch $batch) {
                //                \Fintech\Transaction\Jobs\OrderRiskProfileUpdateJob::dispatch($order_id);
            })
            ->finally(function (Batch $batch) use (&$event) {
                $event->transfer->refresh();
                $timeline = $event->transfer->timeline;
                $timeline[] = [
                    'message' => 'Remittance transfer compliance policy verification completed.',
                    'flag' => 'info',
                    'timestamp' => now(),
                ];
                transaction()->order()->update($event->transfer->getKey(), ['timeline' => $timeline]);
            })
            ->name('Remit compliance verification')
            ->withOption('allowFailures', true)
            ->dispatch();
    }

    /**
     * Handle a job failure.
     */
    public function failed(object $event, \Throwable $exception): void
    {
        transaction()->order()->update($event->transfer->getKey(), [
            'status' => \Fintech\Core\Enums\Transaction\OrderStatus::AdminVerification->value,
            'notes' => $exception->getMessage(),
        ]);
    }
}

<?php

namespace Fintech\Remit\Listeners;

use Fintech\Transaction\Facades\Transaction;
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
        $order = $event->transfer;

        $timeline = $order->timeline;

        \Illuminate\Support\Facades\Bus::batch([
//            [
            new \Fintech\Transaction\Jobs\Compliance\LargeCashTransferPolicy($order->getKey()),
            new \Fintech\Transaction\Jobs\Compliance\LargeVirtualCashTransferPolicy($order->getKey()),
            new \Fintech\Transaction\Jobs\Compliance\ElectronicFundTransferPolicy($order->getKey()),
            new \Fintech\Transaction\Jobs\Compliance\SuspiciousTransactionPolicy($order->getKey()),
            new \Fintech\Transaction\Jobs\Compliance\ClientDueDiligencePolicy($order->getKey()),
            new \Fintech\Transaction\Jobs\Compliance\StructuringDetectionPolicy($order->getKey()),
            new \Fintech\Transaction\Jobs\Compliance\HighRiskCountryTransferPolicy($order->getKey()),
            new \Fintech\Transaction\Jobs\Compliance\PepDetectionPolicy($order->getKey()),
            new \Fintech\Transaction\Jobs\Compliance\HIODetectionPolicy($order->getKey()),
            new \Fintech\Transaction\Jobs\Compliance\AccountVelocityPolicy($order->getKey()),
            new \Fintech\Transaction\Jobs\Compliance\NewProductUsagePolicy($order->getKey()),
            new \Fintech\Transaction\Jobs\Compliance\DormantAccountActivityPolicy($order->getKey()),
            new \Fintech\Transaction\Jobs\Compliance\ThirdPartyTransferPolicy($order->getKey()),
            new \Fintech\Transaction\Jobs\Compliance\VirtualCurrencyTravelPolicy($order->getKey()),
//            ]
        ])
            ->before(function (Batch $batch) use (&$timeline) {
                $timeline[] = [
                    'message' => 'Verifying remittance transfer compliance policy.',
                    'flag' => 'info',
                    'timestamp' => now(),
                ];
            })
            ->then(function (Batch $batch) use (&$timeline) {
                logger("Then: ", [$timeline]);
                $timeline[] = [
                    'message' => 'All Job is Done',
                    'flag' => 'info',
                    'timestamp' => now(),
                ];
                //                \Fintech\Transaction\Jobs\OrderRiskProfileUpdateJob::dispatch($order->getKey());
            })
            ->catch(function (Batch $batch, \Throwable $e) use (&$timeline) {
                $timeline[] = [
                    'message' => 'Remittance transfer compliance policy reported an error: ' . $e->getMessage(),
                    'flag' => 'error',
                    'timestamp' => now(),
                ];
            })
            ->finally(function (Batch $batch) use (&$order, &$timeline) {
                $timeline[] = [
                    'message' => 'Remittance transfer compliance policy verification completed.',
                    'flag' => 'info',
                    'timestamp' => now(),
                ];
                $order = Transaction::order()->update($order->getKey(), ['timeline' => $timeline]);
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
        Transaction::order()->update($event->transfer->getKey(), [
            'status' => \Fintech\Core\Enums\Transaction\OrderStatus::AdminVerification->value,
            'notes' => $exception->getMessage(),
        ]);
    }
}

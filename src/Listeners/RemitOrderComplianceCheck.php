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
            \Fintech\Transaction\Jobs\Compliance\LargeCashTransferJob::dispatch($order->getKey()),
            //            \Fintech\Transaction\Jobs\Compliance\LargeVirtualCashTransferJob::dispatch($order->getKey()),
            //            \Fintech\Transaction\Jobs\Compliance\ElectronicFundTransferJob::dispatch($order->getKey()),
            //            \Fintech\Transaction\Jobs\Compliance\SuspiciousTransactionJob::dispatch($order->getKey()),
            //            \Fintech\Transaction\Jobs\Compliance\ClientDueDiligenceJob::dispatch($order->getKey()),
            //            \Fintech\Transaction\Jobs\Compliance\StructuringDetectionJob::dispatch($order->getKey()),
            //            \Fintech\Transaction\Jobs\Compliance\HighRiskCountryTransferJob::dispatch($order->getKey()),
            //            \Fintech\Transaction\Jobs\Compliance\PepDetectionJob::dispatch($order->getKey()),
            //            \Fintech\Transaction\Jobs\Compliance\HIODetectionJob::dispatch($order->getKey()),
            //            \Fintech\Transaction\Jobs\Compliance\AccountVelocityJob::dispatch($order->getKey()),
            //            \Fintech\Transaction\Jobs\Compliance\NewProductUsageJob::dispatch($order->getKey()),
            //            \Fintech\Transaction\Jobs\Compliance\DormantAccountActivityJob::dispatch($order->getKey()),
            //            \Fintech\Transaction\Jobs\Compliance\ThirdPartyTransferJob::dispatch($order->getKey()),
            //            \Fintech\Transaction\Jobs\Compliance\VirtualCurrencyTravelJob::dispatch($order->getKey()),
        ])
            ->before(function (Batch $batch) use (&$timeline) {
                $timeline[] = [
                    'message' => 'Verifying remittance transfer compliance policy.',
                    'flag' => 'info',
                    'timestamp' => now(),
                ];
            })
            ->progress(function (Batch $batch) use (&$timeline) {
                $timeline[] = [
                    'message' => 'Another Job is Done',
                    'flag' => 'info',
                    'timestamp' => now(),
                ];
            })
            ->then(function (Batch $batch) use (&$timeline) {
                $timeline[] = [
                    'message' => 'Another Job is Done',
                    'flag' => 'info',
                    'timestamp' => now(),
                ];
                //                \Fintech\Transaction\Jobs\OrderRiskProfileUpdateJob::dispatch($order->getKey());
            })
            ->catch(function (Batch $batch, \Throwable $e) use (&$timeline) {
                $timeline[] = [
                    'message' => 'Remittance transfer compliance policy reported an error: '.$e->getMessage(),
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

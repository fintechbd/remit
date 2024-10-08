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
        $transfer = $event->transfer;

        \Illuminate\Support\Facades\Bus::batch([
            \Fintech\Transaction\Jobs\Compliance\LargeCashTransferJob::dispatch($transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\LargeVirtualCashTransferJob::dispatch($transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\ElectronicFundTransferJob::dispatch($transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\SuspiciousTransactionJob::dispatch($transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\ClientDueDiligenceJob::dispatch($transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\StructuringDetectionJob::dispatch($transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\HighRiskCountryTransferJob::dispatch($transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\PepDetectionJob::dispatch($transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\HIODetectionJob::dispatch($transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\AccountVelocityJob::dispatch($transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\NewProductUsageJob::dispatch($transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\DormantAccountActivityJob::dispatch($transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\ThirdPartyTransferJob::dispatch($transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\VirtualCurrencyTravelJob::dispatch($transfer->getKey()),
        ])
            ->before(function (Batch $batch) use (&$transfer) {
                $timeline = $transfer->timeline;
                $timeline[] = [
                    'message' => 'Verifying remittance transfer compliance policy.',
                    'flag' => 'info',
                    'timestamp' => now(),
                ];
                $transfer = Transaction::order()->update($transfer->getKey(), ['timeline' => $timeline]);
            })
            ->progress(function (Batch $batch) use (&$transfer) {
                logger('Batch', [$batch]);
                //                $timeline = $transfer->timeline;
                //                $timeline[] = [
                //                    'message' => 'Testing',
                //                    'flag' => 'info',
                //                    'timestamp' => now(),
                //                ];
                //                $transfer = Transaction::order()->update($transfer->getKey(), ['timeline' => $timeline]);
            })
            ->then(function (Batch $batch) use (&$transfer) {
                \Fintech\Transaction\Jobs\OrderRiskProfileUpdateJob::dispatch($transfer->getKey());
            })
            ->catch(function (Batch $batch, \Throwable $e) use (&$transfer) {
                $timeline = $transfer->timeline;
                $timeline[] = [
                    'message' => 'Remittance transfer compliance policy reported an error: ' . $e->getMessage(),
                    'flag' => 'error',
                    'timestamp' => now(),
                ];
                $transfer = Transaction::order()->update($transfer->getKey(), ['timeline' => $timeline]);
            })
            ->finally(function (Batch $batch) use (&$transfer) {
                $timeline = $transfer->timeline;
                $timeline[] = [
                    'message' => 'Remittance transfer compliance policy verification completed.',
                    'flag' => 'info',
                    'timestamp' => now(),
                ];
                $transfer = Transaction::order()->update($transfer->getKey(), ['timeline' => $timeline]);
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

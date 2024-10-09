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
            \Fintech\Transaction\Jobs\Compliance\LargeCashTransferPolicy::dispatch($order->getKey()),
            \Fintech\Transaction\Jobs\Compliance\LargeVirtualCashTransferPolicy::dispatch($order->getKey()),
            \Fintech\Transaction\Jobs\Compliance\ElectronicFundTransferPolicy::dispatch($order->getKey()),
            \Fintech\Transaction\Jobs\Compliance\SuspiciousTransactionPolicy::dispatch($order->getKey()),
            \Fintech\Transaction\Jobs\Compliance\ClientDueDiligencePolicy::dispatch($order->getKey()),
            \Fintech\Transaction\Jobs\Compliance\StructuringDetectionPolicy::dispatch($order->getKey()),
            \Fintech\Transaction\Jobs\Compliance\HighRiskCountryTransferPolicy::dispatch($order->getKey()),
            \Fintech\Transaction\Jobs\Compliance\PepDetectionPolicy::dispatch($order->getKey()),
            \Fintech\Transaction\Jobs\Compliance\HIODetectionPolicy::dispatch($order->getKey()),
            \Fintech\Transaction\Jobs\Compliance\AccountVelocityPolicy::dispatch($order->getKey()),
            \Fintech\Transaction\Jobs\Compliance\NewProductUsagePolicy::dispatch($order->getKey()),
            \Fintech\Transaction\Jobs\Compliance\DormantAccountActivityPolicy::dispatch($order->getKey()),
            \Fintech\Transaction\Jobs\Compliance\ThirdPartyTransferPolicy::dispatch($order->getKey()),
            \Fintech\Transaction\Jobs\Compliance\VirtualCurrencyTravelPolicy::dispatch($order->getKey()),
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

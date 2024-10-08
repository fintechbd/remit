<?php

namespace Fintech\Remit\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemitOrderComplianceBatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {

        $batch = \Illuminate\Support\Facades\Bus::batch([
            \Fintech\Transaction\Jobs\Compliance\LargeCashTransferJob::dispatch($event->transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\LargeVirtualCashTransferJob::dispatch($event->transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\ElectronicFundTransferJob::dispatch($event->transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\SuspiciousTransactionJob::dispatch($event->transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\ClientDueDiligenceJob::dispatch($event->transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\StructuringDetectionJob::dispatch($event->transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\HighRiskCountryTransferJob::dispatch($event->transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\PepDetectionJob::dispatch($event->transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\HIODetectionJob::dispatch($event->transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\AccountVelocityJob::dispatch($event->transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\NewProductUsageJob::dispatch($event->transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\DormantAccountActivityJob::dispatch($event->transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\ThirdPartyTransferJob::dispatch($event->transfer->getKey()),
            \Fintech\Transaction\Jobs\Compliance\VirtualCurrencyTravelJob::dispatch($event->transfer->getKey()),
        ])->before(function (Batch $batch) use ($event) {
            // The batch has been created but no jobs have been added...
        })->progress(function (Batch $batch) use ($event) {

        })->then(function (Batch $batch) use ($event) {
            \Fintech\Transaction\Jobs\OrderRiskProfileUpdateJob::dispatch($event->transfer->getKey());
        })->catch(function (Batch $batch, \Throwable $e) use ($event) {
            // First batch job failure detected...
        })->finally(function (Batch $batch) use ($event) {
            // The batch has finished executing...
        })->name('Remit compliance verification')->dispatch();
    }

}

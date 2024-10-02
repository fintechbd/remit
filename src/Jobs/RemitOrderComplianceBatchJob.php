<?php

namespace Fintech\Remit\Jobs;

use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Reload\Facades\Reload;
use Fintech\Remit\Events\RemitTransferRequested;
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

        /*        $batch = Bus::batch([
                    new LargeCashTransferJob,
                    new LargeVirtualCashTransferJob,
                    new ElectronicFundTransferJob,
                    new SuspiciousTransactionJob,
                    new ClientDueDiligenceJob,
                    new StructuringDetectionJob,
                    new HighRiskCountryTransferJob,
                    new PepDetectionJob,
                    new HIODetectionJob,
                    new AccountVelocityJob,
                    new NewProductUsageJob,
                    new DormantAccountActivityJob,
                    new ThirdPartyTransferJob,
                    new VirtualCurrencyTravelJob,
                ])->before(function (Batch $batch) {
                    // The batch has been created but no jobs have been added...
                })->progress(function (Batch $batch) {
                    // A single job has completed successfully...
                })->then(function (Batch $batch) {
                    // All jobs completed successfully...
                })->catch(function (Batch $batch, \Throwable $e) {
                    // First batch job failure detected...
                })->finally(function (Batch $batch) {
                    // The batch has finished executing...
                })->dispatch();*/
    }

    /**
     * Handle a failure.
     */
    public function failed(RemitTransferRequested $event, \Throwable $exception): void
    {
        Reload::deposit()->update($event->transfer->getKey(), [
            'status' => OrderStatus::AdminVerification->value,
            'note' => $exception->getMessage(),
        ]);
    }
}

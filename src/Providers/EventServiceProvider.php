<?php

namespace Fintech\Remit\Providers;

use Fintech\Remit\Events\BankTransferRequested;
use Fintech\Remit\Events\RemitTransferFailed;
use Fintech\Remit\Events\RemitTransferRejected;
use Fintech\Remit\Events\RemitTransferRequested;
use Fintech\Remit\Events\RemitTransferVendorAssigned;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        RemitTransferFailed::class => [

        ],
        BankTransferRequested::class => [
            \Fintech\Remit\Jobs\RemitOrderComplianceBatchJob::class,
        ],
        RemitTransferVendorAssigned::class => [

        ],
        RemitTransferRejected::class => [

        ],
    ];
}

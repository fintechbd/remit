<?php

namespace Fintech\Remit\Providers;

use Fintech\Remit\Listeners\RemitOrderComplianceCheck;
use Fintech\Remit\Listeners\RemitOrderPaymentAccepted;
use Fintech\Remit\Listeners\RemitOrderPaymentRejected;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        \Fintech\Remit\Events\BankTransferRequested::class => [
            RemitOrderComplianceCheck::class,
        ],
        \Fintech\Remit\Events\CashPickupRequested::class => [
            RemitOrderComplianceCheck::class,
        ],
        \Fintech\Remit\Events\WalletTransferRequested::class => [
            RemitOrderComplianceCheck::class,
        ],
        \Fintech\Remit\Events\RemitTransferVendorAssigned::class => [

        ],
        \Fintech\Reload\Events\DepositAccepted::class => [
            RemitOrderPaymentAccepted::class
        ],
        \Fintech\Reload\Events\DepositRejected::class => [
            RemitOrderPaymentRejected::class
        ]
    ];
}

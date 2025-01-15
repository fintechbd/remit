<?php

namespace Fintech\Remit\Providers;

use Fintech\Core\Listeners\TriggerNotification;
use Fintech\Remit\Events\BankTransferRequested;
use Fintech\Remit\Events\CashPickupRequested;
use Fintech\Remit\Events\RemitTransferVendorAssigned;
use Fintech\Remit\Events\WalletTransferRequested;
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
        BankTransferRequested::class => [
            RemitOrderComplianceCheck::class,
            TriggerNotification::class,
        ],
        CashPickupRequested::class => [
            RemitOrderComplianceCheck::class,
            TriggerNotification::class,
        ],
        WalletTransferRequested::class => [
            RemitOrderComplianceCheck::class,
            TriggerNotification::class,
        ],
        RemitTransferVendorAssigned::class => [
            TriggerNotification::class,
        ],
        \Fintech\Reload\Events\DepositAccepted::class => [
            RemitOrderPaymentAccepted::class,
        ],
        \Fintech\Reload\Events\DepositRejected::class => [
            RemitOrderPaymentRejected::class,
        ],
    ];
}

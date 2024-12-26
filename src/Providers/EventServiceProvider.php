<?php

namespace Fintech\Remit\Providers;

use Fintech\Remit\Events\BankTransferRequested;
use Fintech\Remit\Events\CashPickupRequested;
use Fintech\Remit\Events\MoneyTransferPayoutRequested;
use Fintech\Remit\Events\RemitTransferVendorAssigned;
use Fintech\Remit\Events\WalletTransferRequested;
use Fintech\Remit\Listeners\MoneyTransferPayoutListener;
use Fintech\Remit\Listeners\RemitOrderComplianceCheck;
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
        ],
        CashPickupRequested::class => [
            RemitOrderComplianceCheck::class,
        ],

        WalletTransferRequested::class => [
            RemitOrderComplianceCheck::class,
        ],

        RemitTransferVendorAssigned::class => [

        ],
        MoneyTransferPayoutRequested::class => [
            MoneyTransferPayoutListener::class
        ]
    ];
}

<?php

namespace Fintech\Remit\Events;

use Fintech\Business\Facades\Business;
use Fintech\Remit\Facades\Remit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CashPickupRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transfer;

    /**
     * Create a new event instance.
     */
    public function __construct($bankTransfer)
    {
        $timeline = $bankTransfer->timeline;

        $service = Business::service()->find($bankTransfer->service_id);

        $timeline[] = [
            'message' => ucwords(strtolower($service->service_name)).' bank transfer requested',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $this->transfer = Remit::bankTransfer()->update($bankTransfer->getKey(), ['timeline' => $timeline]);
    }
}

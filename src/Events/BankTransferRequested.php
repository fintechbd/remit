<?php

namespace Fintech\Remit\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BankTransferRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transfer;

    /**
     * Create a new event instance.
     */
    public function __construct($bankTransfer)
    {
        $timeline = $bankTransfer->timeline;

        $service = business()->service()->find($bankTransfer->service_id);

        $timeline[] = [
            'message' => ucwords(strtolower($service->service_name)).' bank transfer requested',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $this->transfer = remit()->bankTransfer()->update($bankTransfer->getKey(), ['timeline' => $timeline]);
    }
}

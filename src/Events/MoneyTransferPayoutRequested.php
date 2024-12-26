<?php

namespace Fintech\Remit\Events;

use Fintech\Reload\Facades\Reload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MoneyTransferPayoutRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $moneyTransfer;
    /**
     * Create a new event instance.
     */
    public function __construct($moneyTransfer)
    {
        $timeline = $moneyTransfer->timeline;

        $timeline[] = [
            'message' => 'Interac-E-Transfer payout request received',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $this->moneyTransfer = Reload::deposit()->update($moneyTransfer->getKey(), ['timeline' => $timeline]);
    }
}

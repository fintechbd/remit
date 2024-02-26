<?php

namespace Fintech\Remit\Events;

use Fintech\Remit\Models\BankTransfer;
use Fintech\Remit\Models\CashPickup;
use Fintech\Remit\Models\WalletTransfer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemitTransferVendorAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transfer;

    /**
     * Create a new event instance.
     *
     * @param BankTransfer|CashPickup|WalletTransfer
     */
    public function __construct($transfer)
    {
        $this->transfer = $transfer;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}

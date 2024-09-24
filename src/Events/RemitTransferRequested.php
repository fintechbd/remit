<?php

namespace Fintech\Remit\Events;

use Fintech\Core\Abstracts\BaseModel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemitTransferRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param string $transferType
     * @param BaseModel $transfer
     */
    public function __construct(public string $transferType, public BaseModel $transfer)
    {
    }

}

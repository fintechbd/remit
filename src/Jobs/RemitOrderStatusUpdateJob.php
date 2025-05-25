<?php

namespace Fintech\Remit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemitOrderStatusUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new job instance.
     */
    public function __construct($orderId)
    {
        $this->order = transaction()->order()->find($orderId);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        remit()->assignVendor()->orderStatus($this->order);

        $this->removeFromQueue();
    }

    private function removeFromQueue(): void
    {
        $order_data = $this->order->order_data;
        $order_data['queued'] = false;
        transaction()->order()->update($this->order->getKey(), ['order_data' => $order_data]);
    }

    public function failed(\Throwable $exception): void
    {
        $order_data = $this->order->order_data;
        $order_data['queued'] = false;

        transaction()->order()->update($this->order->getKey(), [
            'status' => \Fintech\Core\Enums\Transaction\OrderStatus::AdminVerification->value,
            'notes' => $exception->getMessage(),
            'order_data' => $order_data,
        ]);
    }
}

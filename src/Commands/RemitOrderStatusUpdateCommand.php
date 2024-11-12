<?php

namespace Fintech\Remit\Commands;

use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Remit\Jobs\RemitOrderStatusUpdateJob;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Console\Command;

class RemitOrderStatusUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remit:order-status-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filters['status'] = OrderStatus::Accepted->value;
        $filters['queued'] = false;
        $filters['limit'] = 5;
        $filters['attempt_threshold'] =config('fintech.remit.attempt_threshold', 5);

        Transaction::order()->list($filters)->each(function ($order) {
            $order_data = $order->order_data;
            $order_data['queued'] = true;
            Transaction::order()->update($order->getKey(), ['order_data' => $order_data]);
            RemitOrderStatusUpdateJob::dispatch($order->getKey());
            sleep(1);
        });
    }
}

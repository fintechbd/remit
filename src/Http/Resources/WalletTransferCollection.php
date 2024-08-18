<?php

namespace Fintech\Remit\Http\Resources;

use Fintech\Core\Facades\Core;
use Fintech\Core\Supports\Constant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use stdClass;

class WalletTransferCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($walletTransfer) {
            $data = [
                'id' => $walletTransfer->getKey(),
                'source_country_id' => $walletTransfer->source_country_id ?? null,
                'source_country_name' => null,
                'destination_country_id' => $walletTransfer->destination_country_id ?? null,
                'destination_country_name' => null,
                'parent_id' => $walletTransfer->parent_id ?? null,
                'sender_receiver_id' => $walletTransfer->sender_receiver_id ?? null,
                'sender_receiver_name' => null,
                'user_id' => $walletTransfer->user_id ?? null,
                'user_name' => null,
                'assigned_user_id' => $walletTransfer->assigned_user_id ?? null,
                'assigned_user_name' => null,
                'service_id' => $walletTransfer->service_id ?? null,
                'service_name' => null,
                'transaction_form_id' => $walletTransfer->transaction_form_id ?? null,
                'transaction_form_name' => $walletTransfer->transaction_form_name ?? null,
                'ordered_at' => $walletTransfer->ordered_at ?? null,
                'amount' => $walletTransfer->amount ?? null,
                'currency' => $walletTransfer->currency ?? null,
                'converted_amount' => $walletTransfer->converted_amount ?? null,
                'converted_currency' => $walletTransfer->converted_currency ?? null,
                'order_number' => $walletTransfer->order_number ?? null,
                'risk_profile' => $walletTransfer->risk_profile ?? null,
                'notes' => $walletTransfer->notes ?? null,
                'is_refunded' => $walletTransfer->is_refunded ?? null,
                'order_data' => $walletTransfer->order_data ?? new stdClass,
                'status' => $walletTransfer->status ?? null,
                'created_at' => $walletTransfer->created_at ?? null,
                'updated_at' => $walletTransfer->updated_at ?? null,
            ];

            if (Core::packageExists('MetaData')) {
                $data['source_country_name'] = $walletTransfer->sourceCountry?->name ?? null;
                $data['destination_country_name'] = $walletTransfer->destinationCountry?->name ?? null;
            }
            if (Core::packageExists('Auth')) {
                $data['user_name'] = $walletTransfer->user?->name ?? null;
                $data['sender_receiver_name'] = $walletTransfer->senderReceiver?->name ?? null;
                $data['assigned_user_name'] = $walletTransfer->assignedUser?->name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $walletTransfer->service?->service_name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $walletTransfer->service?->service_name ?? null;
            }
            if (Core::packageExists('Transaction')) {
                $data['transaction_form_name'] = $walletTransfer->transactionForm?->name ?? null;
            }

            return $data;
        })->toArray();
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'options' => [
                'dir' => Constant::SORT_DIRECTIONS,
                'per_page' => Constant::PAGINATE_LENGTHS,
                'sort' => ['id', 'name', 'created_at', 'updated_at'],
            ],
            'query' => $request->all(),
        ];
    }
}

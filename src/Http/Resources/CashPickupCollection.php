<?php

namespace Fintech\Remit\Http\Resources;

use Fintech\Core\Facades\Core;
use Fintech\Core\Supports\Constant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use stdClass;

class CashPickupCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($cashPickup) {
            $data = [
                'id' => $cashPickup->getKey(),
                'source_country_id' => $cashPickup->source_country_id ?? null,
                'source_country_name' => null,
                'destination_country_id' => $cashPickup->destination_country_id ?? null,
                'destination_country_name' => null,
                'parent_id' => $cashPickup->parent_id ?? null,
                'sender_receiver_id' => $cashPickup->sender_receiver_id ?? null,
                'sender_receiver_name' => null,
                'user_id' => $cashPickup->user_id ?? null,
                'user_name' => null,
                'assigned_user_id' => $cashPickup->assigned_user_id ?? null,
                'assigned_user_name' => null,
                'service_id' => $cashPickup->service_id ?? null,
                'service_name' => null,
                'transaction_form_id' => $cashPickup->transaction_form_id ?? null,
                'transaction_form_name' => $cashPickup->transaction_form_name ?? null,
                'ordered_at' => $cashPickup->ordered_at ?? null,
                'amount' => $cashPickup->amount ?? null,
                'currency' => $cashPickup->currency ?? null,
                'converted_amount' => $cashPickup->converted_amount ?? null,
                'converted_currency' => $cashPickup->converted_currency ?? null,
                'order_number' => $cashPickup->order_number ?? null,
                'risk_profile' => $cashPickup->risk_profile ?? null,
                'notes' => $cashPickup->notes ?? null,
                'is_refunded' => $cashPickup->is_refunded ?? null,
                'order_data' => $cashPickup->order_data ?? new stdClass,
                'status' => $cashPickup->status ?? null,
                'created_at' => $cashPickup->created_at ?? null,
                'updated_at' => $cashPickup->updated_at ?? null,
            ];

            if (Core::packageExists('MetaData')) {
                $data['source_country_name'] = $cashPickup->sourceCountry?->name ?? null;
                $data['destination_country_name'] = $cashPickup->destinationCountry?->name ?? null;
            }
            if (Core::packageExists('Auth')) {
                $data['user_name'] = $cashPickup->user?->name ?? null;
                $data['sender_receiver_name'] = $cashPickup->senderReceiver?->name ?? null;
                $data['assigned_user_name'] = $cashPickup->assignedUser?->name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $cashPickup->service?->service_name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $cashPickup->service?->service_name ?? null;
            }
            if (Core::packageExists('Transaction')) {
                $data['transaction_form_name'] = $cashPickup->transactionForm?->name ?? null;
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

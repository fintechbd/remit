<?php

namespace Fintech\Remit\Http\Resources;

use Fintech\Core\Facades\Core;
use Fintech\Core\Supports\Constant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use stdClass;

class BankTransferCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($bankTransfer) {
            $data = [
                'id' => $bankTransfer->getKey(),
                'source_country_id' => $bankTransfer->source_country_id ?? null,
                'source_country_name' => null,
                'destination_country_id' => $bankTransfer->destination_country_id ?? null,
                'destination_country_name' => null,
                'parent_id' => $bankTransfer->parent_id ?? null,
                'sender_receiver_id' => $bankTransfer->sender_receiver_id ?? null,
                'sender_receiver_name' => null,
                'user_id' => $bankTransfer->user_id ?? null,
                'user_name' => null,
                'assigned_user_id' => $bankTransfer->assigned_user_id ?? null,
                'assigned_user_name' => null,
                'service_id' => $bankTransfer->service_id ?? null,
                'service_name' => null,
                'service_vendor_id' => $bankTransfer->service_vendor_id ?? config('fintech.business.default_vendor'),
                'service_vendor_name' => null,
                'vendor' => $bankTransfer->vendor ?? config('fintech.business.default_vendor_name'),
                'transaction_form_id' => $bankTransfer->transaction_form_id ?? null,
                'transaction_form_name' => $bankTransfer->transaction_form_name ?? null,
                'ordered_at' => $bankTransfer->ordered_at ?? null,
                'amount' => $bankTransfer->amount ?? null,
                'currency' => $bankTransfer->currency ?? null,
                'converted_amount' => $bankTransfer->converted_amount ?? null,
                'converted_currency' => $bankTransfer->converted_currency ?? null,
                'order_number' => $bankTransfer->order_number ?? null,
                'risk_profile' => $bankTransfer->risk_profile ?? null,
                'notes' => $bankTransfer->notes ?? null,
                'is_refunded' => $bankTransfer->is_refunded ?? null,
                'order_data' => $bankTransfer->order_data ?? new stdClass,
                'status' => $bankTransfer->status ?? null,
                'created_at' => $bankTransfer->created_at ?? null,
                'updated_at' => $bankTransfer->updated_at ?? null,
            ];

            if (Core::packageExists('MetaData')) {
                $data['source_country_name'] = $bankTransfer->sourceCountry?->name ?? null;
                $data['destination_country_name'] = $bankTransfer->destinationCountry?->name ?? null;
            }
            if (Core::packageExists('Auth')) {
                $data['user_name'] = $bankTransfer->user?->name ?? null;
                $data['sender_receiver_name'] = $bankTransfer->senderReceiver?->name ?? null;
                $data['assigned_user_name'] = $bankTransfer->assignedUser?->name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_vendor_name'] = $bankTransfer->serviceVendor?->service_vendor_name ?? null;
                $data['service_name'] = $bankTransfer->service?->service_name ?? null;
            }
            if (Core::packageExists('Transaction')) {
                $data['transaction_form_name'] = $bankTransfer->transactionForm?->name ?? null;
            }

            $data['assignable'] = ! is_int($data['assigned_user_id']);

            $data['trackable'] = $data['service_vendor_id'] != config('fintech.business.default_vendor');

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

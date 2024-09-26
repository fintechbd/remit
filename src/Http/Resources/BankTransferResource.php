<?php

namespace Fintech\Remit\Http\Resources;

use Fintech\Core\Facades\Core;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use stdClass;

use function currency;

class BankTransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->getKey(),
            'source_country_id' => $this->source_country_id ?? null,
            'source_country_name' => null,
            'destination_country_id' => $this->destination_country_id ?? null,
            'destination_country_name' => null,
            'parent_id' => $this->parent_id ?? null,
            'sender_receiver_id' => $this->sender_receiver_id ?? null,
            'sender_receiver_name' => null,
            'user_id' => $this->user_id ?? null,
            'user_name' => null,
            'assigned_user_id' => $this->assigned_user_id ?? null,
            'assigned_user_name' => null,
            'service_id' => $this->service_id ?? null,
            'service_name' => null,
            'service_vendor_id' => $this->service_vendor_id ?? config('fintech.business.default_vendor'),
            'service_vendor_name' => null,
            'vendor' => $this->vendor ?? config('fintech.business.default_vendor_name'),
            'transaction_form_id' => $this->transaction_form_id ?? null,
            'transaction_form_name' => $this->transaction_form_name ?? null,
            'ordered_at' => $this->ordered_at ?? null,
            'amount' => $this->amount ?? null,
            'currency' => $this->currency ?? null,
            'converted_amount' => $this->converted_amount ?? null,
            'converted_currency' => $this->converted_currency ?? null,
            'order_number' => $this->order_number ?? null,
            'risk_profile' => $this->risk_profile ?? null,
            'notes' => $this->notes ?? null,
            'is_refunded' => $this->is_refunded ?? false,
            'order_data' => $this->order_data ?? new stdClass,
            'status' => $this->status ?? null,
            'created_at' => $this->created_at ?? null,
            'updated_at' => $this->updated_at ?? null,
            'timeline' => $this->timeline ?? [],
        ];

        $data['amount_formatted'] = currency($data['amount'], $data['currency'])->format();
        $data['converted_amount_formatted'] = currency($data['converted_amount'], $data['converted_currency'])->format();

        if (Core::packageExists('MetaData')) {
            $data['source_country_name'] = $this->sourceCountry?->name ?? null;
            $data['destination_country_name'] = $this->destinationCountry?->name ?? null;
        }
        if (Core::packageExists('Auth')) {
            $data['user_name'] = $this->user?->name ?? null;
            $data['sender_receiver_name'] = $this->senderReceiver?->name ?? null;
            $data['assigned_user_name'] = $this->assignedUser?->name ?? null;
        }
        if (Core::packageExists('Transaction')) {
            $data['transaction_form_name'] = $this->transactionForm?->name ?? null;
        }

        if (Core::packageExists('Business')) {
            $data['service_vendor_name'] = $this->serviceVendor?->service_vendor_name ?? null;
            $data['service_name'] = $this->service?->service_name ?? null;
        }
        $data['assignable'] = ($data['assigned_user_id'] == null || $data['assigned_user_id'] == $request->user()->getKey());
        $data['trackable'] = $data['service_vendor_id'] != config('fintech.business.default_vendor');

        return $data;
    }
}

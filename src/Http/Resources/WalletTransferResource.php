<?php

namespace Fintech\Remit\Http\Resources;

use Fintech\Core\Facades\Core;
use Fintech\Core\Traits\RestApi\CompliancePolicyTable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use stdClass;

use function currency;

class WalletTransferResource extends JsonResource
{
    use CompliancePolicyTable;

    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
                'risk' => $this->risk ?? null,
                'is_refunded' => $this->is_refunded ?? null,
                'order_data' => $this->order_data ?? null,
                'assigned_user_name' => $this->assignedUser?->name ?? null,
                'assignable' => ($this->assigned_user_id == null || $this->assigned_user_id == $request->user()->getKey()),
                'trackable' => $this->service_vendor_id != config('fintech.business.default_vendor'),
            ] + $this->commonAttributes();

        $this->renderPolicyData($this->order_data);

        return $data;
    }
}

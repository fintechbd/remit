<?php

namespace Fintech\Remit\Http\Resources;

use Fintech\Core\Facades\Core;
use Fintech\Core\Supports\Constant;
use Fintech\Core\Traits\RestApi\CompliancePolicyTable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use stdClass;

use function currency;

class CashPickupCollection extends ResourceCollection
{
    use CompliancePolicyTable;

    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($item) use ($request) {
            $data = [
                    'risk' => $item->risk ?? null,
                    'is_refunded' => $item->is_refunded ?? null,
                    'order_data' => $item->order_data ?? null,
                    'assigned_user_name' => $item->assignedUser?->name ?? null,
                    'assignable' => ($item->assigned_user_id == null || $item->assigned_user_id == $request->user()->getKey()),
                    'trackable' => $item->service_vendor_id != config('fintech.business.default_vendor'),
                ] + $item->commonAttributes();

            $this->renderPolicyData($item->order_data);

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

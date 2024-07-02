<?php

namespace Fintech\Remit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AssignableVendorCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($vendor) {
            return [
                'id' => $vendor->getKey() ?? null,
                'service_vendor_name' => $vendor->service_vendor_name ?? null,
                'service_vendor_slug' => $vendor->service_vendor_slug ?? null,
                'service_vendor_data' => $vendor->service_vendor_data ?? null,
                'service_vendor_logo_svg' => $vendor->getFirstMediaUrl('logo_svg') ?? null,
                'service_vendor_logo_png' => $vendor->getFirstMediaUrl('logo_png') ?? null,
                'enabled' => $vendor->enabled ?? null,
            ];
        })->toArray();
    }
}

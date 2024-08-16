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
                'name' => $vendor->service_vendor_name ?? null,
                'slug' => $vendor->service_vendor_slug ?? null,
                'data' => $vendor->service_vendor_data ?? null,
                'logo_svg' => $vendor->getFirstMediaUrl('logo_svg') ?? null,
                'logo_png' => $vendor->getFirstMediaUrl('logo_png') ?? null,
                'enabled' => $vendor->enabled ?? null,
            ];
        })->toArray();
    }
}

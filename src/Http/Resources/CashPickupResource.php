<?php

namespace Fintech\Remit\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CashPickupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}

<?php

namespace Fintech\Remit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        return parent::toArray($request);
    }
}

<?php

namespace Fintech\Remit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountVerificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'account_no' => $this->account_no ?? null,
            'account_title' => $this->account_title ?? null,
            'name' => $this->wallet['name'] ?? null,
        ];
    }
}

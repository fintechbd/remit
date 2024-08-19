<?php

namespace Fintech\Remit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignVendorQuotaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return match ($request->input('vendor_slug')) {
            'emqapi' => $this->formatEmqApi($request),
            'transfast' => $this->formatTransFast($request),
            'valyou' => $this->formatValyou($request),
            'agrani' => $this->formatAgrani($request),
            'citybank' => $this->formatCitybank($request),
            'islamibank' => $this->formatIslamibank($request),
            'meghnabank' => $this->formatMeghnabank($request),
            default => parent::toArray($request),
        };
    }

    private function formatAgrani(Request $request): array
    {
        return parent::toArray($request);
    }

    private function formatEmqApi(Request $request): array
    {
        return parent::toArray($request);
    }


    private function formatTransFast(Request $request): array
    {
        return parent::toArray($request);
    }

    private function formatValyou(Request $request): array
    {
        return parent::toArray($request);
    }

    private function formatCitybank(Request $request): array
    {
        return parent::toArray($request);
    }

    private function formatIslamibank(Request $request): array
    {
        return parent::toArray($request);
    }

    private function formatMeghnabank(Request $request): array
    {
        return parent::toArray($request);
    }


}

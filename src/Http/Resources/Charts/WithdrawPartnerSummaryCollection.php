<?php

namespace Fintech\Remit\Http\Resources\Charts;

use Fintech\Core\Supports\Constant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class WithdrawPartnerSummaryCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($item) {
            return $item;
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
                'sort' => ['count', 'status'],
                'filter' => [],
                'columns' => [
                    'vendor',
                    'count',
                    'total'
                ],
                'labels' => [
                    'total' => 'Total Amount (CAD)',
                    'count' => 'No of Transactions',
                    'vendor' => 'Name',
                ]
            ],
            'query' => $request->all(),
        ];
    }
}


<?php

namespace Fintech\Remit\Http\Controllers\Charts;

use App\Http\Controllers\Controller;
use Fintech\Remit\Http\Resources\Charts\WithdrawPartnerSummaryCollection;
use Illuminate\Http\Request;

class WithdrawPartnerSummaryController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $orders = collect([
            ['vendor' => 'Agrani bank', 'count' => '65', 'total' => '35700'],
            ['vendor' => 'Islami Bank', 'count' => '15', 'total' => '12050'],
            ['vendor' => 'Wallet', 'count' => '29', 'total' => '2100'],
            ['vendor' => 'Meghna Bank', 'count' => '5', 'total' => '21000'],
        ]);

        return new WithdrawPartnerSummaryCollection($orders);
    }
}

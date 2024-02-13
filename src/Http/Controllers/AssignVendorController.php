<?php

namespace Fintech\Remit\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class AssignVendorController extends Controller
{
    use ApiResponseTrait;

    /**
     * Store a newly created resource in storage.
     */
    public function available(string $id)
    {
        try {

            $order = Transaction::order()->find($id);

            if (! $order) {
                throw (new ModelNotFoundException)->setModel(config('fintech.transaction.order_model'), $id);
            }

            $serviceVendors = \Fintech\Business\Facades\Business::serviceVendor()->list([
                'service_id_array' => [$order->service_id],
                'paginate' => false,
            ]);
            dd($serviceVendors);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

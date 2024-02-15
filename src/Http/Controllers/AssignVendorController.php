<?php

namespace Fintech\Remit\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Remit\Http\Requests\AssignableVendorInfoRequest;
use Fintech\Remit\Http\Resources\AssignableVendorCollection;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignVendorController extends Controller
{
    use ApiResponseTrait;

    /**
     * Store a newly created resource in storage.
     */
    public function available(string $id): JsonResponse|AssignableVendorCollection
    {
        try {
            $order = Transaction::order()->find($id);

            if (! $order) {
                throw (new ModelNotFoundException)->setModel(config('fintech.transaction.order_model'), $id);
            }

            $serviceVendors = \Fintech\Business\Facades\Business::serviceVendor()->list([
                'service_id_array' => [$order->service_id],
                'enabled' => true,
                'paginate' => false,
            ]);

            return new AssignableVendorCollection($serviceVendors);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function vendor(AssignableVendorInfoRequest $request): JsonResponse
    {
        try {
            $order = Transaction::order()->find($request->input('order_id'));

            if (! $order) {
                throw (new ModelNotFoundException)->setModel(config('fintech.transaction.order_model'), $request->input('order_id'));
            }

            $availableVendors = config('fintech.remit.providers');

            if (! array_key_exists($request->input('vendor_slug'), $availableVendors)) {
                throw new \ErrorException('Service Vendor is not available on the configuration.');
            }

            $jsonResponse = [];

            return $this->success($jsonResponse);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    private function defaultVendorData(): array
    {
        return [
            'balance' => 'test',
            'approved' => true,
        ];
    }

    private function cityBankVendorData(): array
    {

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

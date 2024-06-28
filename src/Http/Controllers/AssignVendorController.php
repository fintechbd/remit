<?php

namespace Fintech\Remit\Http\Controllers;

use App\Http\Controllers\Controller;
use ErrorException;
use Exception;
use Fintech\Business\Facades\Business;
use Fintech\Remit\Contracts\OrderQuotation;
use Fintech\Remit\Http\Requests\AssignableVendorInfoRequest;
use Fintech\Remit\Http\Resources\AssignableVendorCollection;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class AssignVendorController extends Controller
{
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

            $serviceVendors = Business::serviceVendor()->list([
                'service_id_array' => [$order->service_id],
                'enabled' => true,
                'paginate' => false,
            ]);

            return new AssignableVendorCollection($serviceVendors);

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function vendor(AssignableVendorInfoRequest $request): JsonResponse
    {
        $order_id = $request->input('order_id');

        $service_vendor_slug = $request->input('vendor_slug');

        try {
            $order = Transaction::order()->find($order_id);

            if (! $order) {
                throw (new ModelNotFoundException)->setModel(config('fintech.transaction.order_model'), $order_id);
            }

            $availableVendors = config('fintech.remit.providers');

            if (! isset($availableVendors[$service_vendor_slug])) {
                throw new ErrorException('Service Vendor is not available on the configuration.');
            }

            $vendor = $availableVendors[$service_vendor_slug];

            $driverClass = $vendor['driver'];

            $instance = App::make($driverClass);

            if (! $instance instanceof OrderQuotation) {
                throw new ErrorException('Service Vendor Class is not instance of `Fintech\Remit\Contracts\OrderQuotation` interface.');
            }

            $jsonResponse = $instance->requestQuotation($order);

            return response()->success($jsonResponse);

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception->getMessage());
        }
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

    private function defaultVendorData(): array
    {
        return [
            'balance' => 'test',
            'approved' => true,
        ];
    }

    private function cityBankVendorData(): array {}
}

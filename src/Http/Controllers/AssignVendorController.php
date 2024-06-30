<?php

namespace Fintech\Remit\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Remit\Facades\Remit;
use Fintech\Remit\Http\Requests\AssignableVendorInfoRequest;
use Fintech\Remit\Http\Resources\AssignableVendorCollection;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class AssignVendorController extends Controller
{
    private function getOrder($id): BaseModel
    {
        $order = Transaction::order()->find($id);

        if (! $order) {
            throw (new ModelNotFoundException)->setModel(config('fintech.transaction.order_model'), $id);
        }

        return $order;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function available(string $id): JsonResponse|AssignableVendorCollection
    {
        try {

            $order = $this->getOrder($id);

            $serviceVendors = Remit::assignVendor()->availableVendors($order);

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

            $order = $this->getOrder($order_id);

            $jsonResponse = Remit::assignVendor()->requestQuote($order, $service_vendor_slug);

            return response()->success($jsonResponse);

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception->getMessage());
        }
    }

    public function process(AssignableVendorInfoRequest $request): JsonResponse
    {
        $order_id = $request->input('order_id');

        $service_vendor_slug = $request->input('vendor_slug');

        try {

            $order = $this->getOrder($order_id);

            $jsonResponse = Remit::assignVendor()->requestQuote($order, $service_vendor_slug);

            return response()->success($jsonResponse);

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception->getMessage());
        }
    }

    public function status(AssignableVendorInfoRequest $request): JsonResponse
    {
        $order_id = $request->input('order_id');

        $service_vendor_slug = $request->input('vendor_slug');

        try {

            $order = $this->getOrder($order_id);

            $jsonResponse = Remit::assignVendor()->requestQuote($order, $service_vendor_slug);

            return response()->success($jsonResponse);

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception->getMessage());
        }
    }

    public function release(AssignableVendorInfoRequest $request): JsonResponse
    {
        $order_id = $request->input('order_id');

        $service_vendor_slug = $request->input('vendor_slug');

        try {

            $order = $this->getOrder($order_id);

            $jsonResponse = Remit::assignVendor()->requestQuote($order, $service_vendor_slug);

            return response()->success($jsonResponse);

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception->getMessage());
        }
    }

    public function cancel(AssignableVendorInfoRequest $request): JsonResponse
    {
        $order_id = $request->input('order_id');

        $service_vendor_slug = $request->input('vendor_slug');

        try {

            $order = $this->getOrder($order_id);

            $jsonResponse = Remit::assignVendor()->requestQuote($order, $service_vendor_slug);

            return response()->success($jsonResponse);

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception->getMessage());
        }
    }
}

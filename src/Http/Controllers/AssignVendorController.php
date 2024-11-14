<?php

namespace Fintech\Remit\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Fintech\Auth\Facades\Auth;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Remit\Exceptions\AlreadyAssignedException;
use Fintech\Remit\Facades\Remit;
use Fintech\Remit\Http\Requests\AssignableVendorInfoRequest;
use Fintech\Remit\Http\Requests\RemitCancelAmendmentRequest;
use Fintech\Remit\Http\Resources\AssignableVendorCollection;
use Fintech\Remit\Http\Resources\AssignVendorQuotaResource;
use Fintech\Remit\Http\Resources\AssignVendorStatusResource;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignVendorController extends Controller
{
    public function available(string $order_Id): JsonResponse|AssignableVendorCollection
    {
        try {

            $order = $this->getOrder($order_Id);

            $serviceVendors = Remit::assignVendor()->availableVendors($order, request()->user()->id);

            return new AssignableVendorCollection($serviceVendors);

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (AlreadyAssignedException $exception) {

            return response()->locked($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    private function getOrder($id)
    {
        $order = Transaction::order()->find($id);

        if (! $order) {
            throw (new ModelNotFoundException)->setModel(config('fintech.transaction.order_model'), $id);
        }

        return $order;
    }

    public function quotation(AssignableVendorInfoRequest $request): JsonResponse|AssignVendorQuotaResource
    {
        $order_id = $request->input('order_id');

        $service_vendor_slug = $request->input('vendor_slug');

        try {

            $order = $this->getOrder($order_id);

            $quotation = Remit::assignVendor()->requestQuote($order, $service_vendor_slug);

            return new AssignVendorQuotaResource($quotation->toArray());

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    public function process(AssignableVendorInfoRequest $request): JsonResponse
    {
        $order_id = $request->input('order_id');

        $service_vendor_slug = $request->input('vendor_slug');

        try {
            $order = $this->getOrder($order_id);

            $verdict = Remit::assignVendor()->processOrder($order, $service_vendor_slug);

            if (! $verdict->status) {
                throw new UpdateOperationException(__('remit::messages.assign_vendor.failed', ['slug' => $service_vendor_slug, 'error' => $verdict->message]));
            }

            return response()->success(__('remit::messages.assign_vendor.success', ['slug' => ucfirst($service_vendor_slug)]));

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    public function track(string $order_id, Request $request): JsonResponse|AssignVendorStatusResource
    {
        try {

            $order = $this->getOrder($order_id);

            $verdict = Remit::assignVendor()->trackOrder($order, $request->user());

            unset($verdict->amount, $verdict->charge, $verdict->discount, $verdict->commission);

            return new AssignVendorStatusResource($verdict);

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    public function cancel(RemitCancelAmendmentRequest $request): JsonResponse
    {
        $order_id = $request->input('order_id');

        try {

            $order = $this->getOrder($order_id);

            $jsonResponse = Remit::assignVendor()->cancelOrder($order);

            return response()->success($jsonResponse);

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    public function amendment(RemitCancelAmendmentRequest $request): JsonResponse
    {
        $order_id = $request->input('order_id');

        try {

            $order = $this->getOrder($order_id);

            $jsonResponse = Remit::assignVendor()->amendmentOrder($order);

            return response()->success($jsonResponse);

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    public function overwrite(AssignableVendorInfoRequest $request): JsonResponse
    {
        $order_id = $request->input('order_id');

        $service_vendor_slug = $request->input('vendor_slug');

        try {

            $order = $this->getOrder($order_id);

            $jsonResponse = Remit::assignVendor()->amendmentOrder($order, $service_vendor_slug);

            return response()->success($jsonResponse);

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    public function release(AssignableVendorInfoRequest $request): JsonResponse
    {
        $order_id = $request->input('order_id');

        try {
            $order = $this->getOrder($order_id);

            if (! Transaction::order()->update($order->getKey(), ['assigned_user_id' => null, 'service_vendor_id' => null, 'vendor' => null])) {

                throw (new UpdateOperationException)->setModel(config('fintech.remit.bank_transfer_model'), $order_id);
            }

            return response()->updated(__('restapi::messages.resource.updated', ['model' => 'Order']));

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }
}

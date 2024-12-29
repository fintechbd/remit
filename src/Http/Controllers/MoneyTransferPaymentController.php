<?php

namespace Fintech\Remit\Http\Controllers;

use Exception;
use Fintech\Business\Facades\Business;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Remit\Events\MoneyTransferPayoutRequested;
use Fintech\Remit\Http\Requests\MoneyTransferPaymentRequest;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Controller;

class MoneyTransferPaymentController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @throws UpdateOperationException
     */
    public function __invoke(string $id, MoneyTransferPaymentRequest $request): \Illuminate\Http\JsonResponse
    {
        try {

            $moneyTransfer = Transaction::order()->find($id);

            if (! $moneyTransfer) {
                throw (new ModelNotFoundException)->setModel(config('fintech.transaction.order_model'), $id);
            }

            $inputs = $request->validated();

            $orderData = $moneyTransfer->order_data ?? [];

            $orderData['interac_email'] = $inputs['interac_email'];

            $payoutVendor = Business::serviceVendor()->findWhere([
                'service_vendor_slug' => $inputs['vendor'] ?? 'leatherback',
                'enabled' => true,
                'paginate' => false,
            ]);

            if (! $payoutVendor) {

                throw (new ModelNotFoundException)->setModel(config('fintech.business.service_vendor_model'), $inputs['vendor']);
            }

            $data = [
                'status' => OrderStatus::Pending,
                'order_data' => $orderData,
                'service_vendor_id' => $payoutVendor->getKey(),
                'vendor' => $payoutVendor->service_vendor_slug,
            ];

            if (! Transaction::order()->update($id, $data)) {

                throw (new UpdateOperationException)->setModel(config('fintech.transaction.order_model'), $id);
            }

            $service = $moneyTransfer->service;

            event(new MoneyTransferPayoutRequested($moneyTransfer));

            return response()->updated(__('core::messages.transaction.request_created', ['service' => ucwords($service->service_name).' Payment']));

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }
}

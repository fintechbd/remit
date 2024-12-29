<?php

namespace Fintech\Remit\Http\Controllers;

use Exception;
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

            if (! Transaction::order()->update($id, ['status' => OrderStatus::Pending, 'order_data' => $orderData])) {

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

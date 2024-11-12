<?php

namespace Fintech\Remit\Services;

use ErrorException;
use Fintech\Auth\Facades\Auth;
use Fintech\Business\Facades\Business;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Exceptions\VendorNotFoundException;
use Fintech\Core\Supports\AssignVendorVerdict;
use Fintech\Remit\Contracts\MoneyTransfer;
use Fintech\Remit\Exceptions\AlreadyAssignedException;
use Fintech\Remit\Exceptions\RemitException;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;

class AssignVendorService
{
    use \Fintech\Core\Traits\HasFindWhereSearch;

    private $serviceVendorModel;

    private MoneyTransfer $serviceVendorDriver;

    /**
     * @throws AlreadyAssignedException
     * @throws UpdateOperationException
     */
    public function availableVendors(BaseModel $order, $requestingUserId): Collection
    {
        $requestedUser = Auth::user()->find($requestingUserId);

        if ($order->assigned_user_id != null
            && $order->assigned_user_id != $requestingUserId) {
            throw new AlreadyAssignedException(__('remit::messages.assign_vendor.already_assigned'));
        }

        $timeline = $order->timeline;

        $service = $order->service;

        $timeline[] = [
            'message' => "Assigning {$requestedUser->name} for managing ".ucwords(strtolower($service->service_name)).' money transfer request',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $timeline[] = [
            'message' => "{$requestedUser->name} requested available vendor list for ".ucwords(strtolower($service->service_name)).' money transfer request',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        if ($order->assigned_user_id == null
            && ! Transaction::order()->update($order->getKey(), ['assigned_user_id' => $requestingUserId, 'timeline' => $timeline])) {
            throw new UpdateOperationException(__('remit::messages.assign_vendor.assigned_user_failed'));
        }

        return Business::serviceVendor()->list([
            'service_id_array' => [$order->service_id],
            'enabled' => true,
            'paginate' => false,
        ]);
    }

    /**
     * @throws ErrorException
     * @throws VendorNotFoundException|UpdateOperationException
     */
    public function requestQuote(BaseModel $order, string $vendor_slug): AssignVendorVerdict
    {
        $this->initiateVendor($vendor_slug);

        $timeline = $order->timeline;

        $service = $order->service;

        $timeline[] = [
            'message' => "Requesting ({$this->serviceVendorModel->service_vendor_name}) for ".ucwords(strtolower($service->service_name)).' money transfer quotation',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $quotation = $this->serviceVendorDriver->requestQuote($order);

        if (! $quotation->status) {
            $timeline[] = [
                'message' => "({$this->serviceVendorModel->service_vendor_name}) reported error : ".$quotation->message,
                'flag' => 'error',
                'timestamp' => now(),
            ];
        }

        if (! Transaction::order()->update($order->getKey(), ['status' => OrderStatus::Processing, 'timeline' => $timeline])) {
            throw new UpdateOperationException;
        }

        return $quotation;
    }

    /**
     * @throws ErrorException
     * @throws VendorNotFoundException
     */
    public function processOrder(BaseModel $order, string $vendor_slug): AssignVendorVerdict
    {
        $this->initiateVendor($vendor_slug);

        $assignedUser = $order->assignedUser;
        $service = $order->service;
        $serviceName = ucwords(strtolower($service->service_name));
        $vendorName = $this->serviceVendorModel->service_vendor_name;

        $data = $order->timeline;

        $data['timeline'][] = [
            'message' => "{$assignedUser->name} assigned ({$vendorName}) to process the {$serviceName} money transfer request",
            'flag' => 'info',
            'timestamp' => now(),
        ];
        $data['vendor'] = $vendor_slug;
        $data['service_vendor_id'] = $this->serviceVendorModel->getKey();

        $verdict = $this->serviceVendorDriver->executeOrder($order);

        $data['timeline'][] = $verdict->timeline;
        $data['notes'] = $verdict->message;
        $data['order_data'] = $order->order_data;
        $data['order_data']['vendor_data']['payout_info'] = $verdict->toArray();

        if (! $verdict->status) {
            $data['status'] = OrderStatus::AdminVerification->value;
            $data['timeline'][] = [
                'message' => "Updating {$serviceName} money transfer request status. Requires ".OrderStatus::AdminVerification->label().' confirmation',
                'flag' => 'warn',
                'timestamp' => now(),
            ];
        } else {
            $data['status'] = OrderStatus::Accepted->value;
            $data['timeline'][] = [
                'message' => "Waiting for ({$vendorName}) to process {$serviceName} money transfer request.",
                'flag' => 'info',
                'timestamp' => now(),
            ];
        }

        if (! Transaction::order()->update($order->getKey(), $data)) {
            throw new \ErrorException(__('remit::messages.assign_vendor.failed', [
                'slug' => $vendor_slug,
            ]));
        }

        return $verdict;
    }

    /**
     * @throws RemitException
     * @throws ErrorException
     * @throws VendorNotFoundException
     */
    public function trackOrder(BaseModel $order): mixed
    {

        if ($order->service_vendor_id == config('fintech.business.default_vendor')) {
            throw new RemitException(__('remit::messages.assign_vendor.not_assigned'));
        }

        $this->initiateVendor($order->vendor);

        return $this->serviceVendorDriver->trackOrder($order);
    }

    /**
     * @throws ErrorException
     */
    public function cancelOrder(BaseModel $order): mixed
    {
        $this->initiateVendor($order->vendor);

        return $this->serviceVendorDriver->orderStatus($order);
    }

    /**
     * @throws RemitException
     */
    public function orderStatus(BaseModel $order): mixed
    {

        if ($order->service_vendor_id == config('fintech.business.default_vendor')) {
            throw new RemitException(__('remit::messages.assign_vendor.not_assigned'));
        }

        $this->initiateVendor($order->vendor);

        return $this->serviceVendorDriver->orderStatus($order);
    }

    public function amendmentOrder(BaseModel $order): mixed
    {
        $this->initiateVendor($order->vendor);

        return $this->serviceVendorDriver->orderStatus($order);
    }

    /**
     * @throws VendorNotFoundException
     */
    private function initiateVendor(string $slug): void
    {
        $availableVendors = config('fintech.remit.providers', []);

        if (! isset($availableVendors[$slug])) {
            throw new VendorNotFoundException(ucfirst($slug));
        }

        $this->serviceVendorModel = Business::serviceVendor()->findWhere(['service_vendor_slug' => $slug, 'enabled']);

        if (! $this->serviceVendorModel) {
            throw (new ModelNotFoundException)->setModel(config('fintech.business.service_vendor_model'), $slug);
        }

        $this->serviceVendorDriver = App::make($availableVendors[$slug]['driver']);
    }
}

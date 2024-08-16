<?php

namespace Fintech\Remit\Services;

use ErrorException;
use Fintech\Business\Facades\Business;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Remit\Contracts\OrderInquiry;
use Fintech\Remit\Contracts\OrderQuotation;
use Fintech\Remit\Contracts\ProceedOrder;
use Fintech\Remit\Exceptions\AlreadyAssignedException;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;

class AssignVendorService
{
    /**
     * @throws ErrorException
     */
    private function getVendorInstance(string $slug): mixed
    {
        $availableVendors = config('fintech.remit.providers', []);

        if (!isset($availableVendors[$slug])) {
            throw new ErrorException(__('remit::message.assign_vendor.not_found', ['slug' => ucfirst($slug)]));
        }

        $vendor = $availableVendors[$slug];

        $driverClass = $vendor['driver'];

        return App::make($driverClass);
    }

    /**
     * @throws AlreadyAssignedException
     * @throws UpdateOperationException
     */
    public function availableVendors(BaseModel $order, $requestingUserId): Collection
    {
        if ($order->assigned_user_id != null
            && $order->assigned_user_id != $requestingUserId) {
            throw new AlreadyAssignedException(__('remit::assign_vendor.already_assigned'));
        }

        if ($order->assigned_user_id == null
            && !Transaction::order()->update($order->getKey(), ['assigned_user_id' => $requestingUserId])) {
            throw new UpdateOperationException(__('remit::assign_vendor.assigned_user_failed'));
        }

        return Business::serviceVendor()->list([
            'service_id_array' => [$order->service_id],
            'enabled' => true,
            'paginate' => false,
        ]);
    }

    /**
     * @throws ErrorException
     */
    public function requestQuote(BaseModel $order, string $vendor_slug): mixed
    {
        $vendor = $this->getVendorInstance($vendor_slug);

        if (!$vendor instanceof OrderQuotation) {
            throw new ErrorException('Service Vendor Class is not instance of `Fintech\Remit\Contracts\OrderQuotation` interface.');
        }

        return $vendor->requestQuote($order);
    }

    /**
     * @throws ErrorException
     */
    public function processOrder(BaseModel $order, string $vendor_slug): mixed
    {
        $vendor = $this->getVendorInstance($vendor_slug);

        if (!$vendor instanceof ProceedOrder) {
            throw new ErrorException('Service Vendor Class is not instance of `Fintech\Remit\Contracts\ProceedOrder` interface.');
        }

        return $vendor->processOrder($order);
    }

    /**
     * @throws ErrorException
     */
    public function orderStatus(BaseModel $order): mixed
    {
        $vendor = $this->getVendorInstance($vendor_slug);

        if (!$vendor instanceof OrderInquiry) {
            throw new ErrorException('Service Vendor Class is not instance of `Fintech\Remit\Contracts\OrderInquiry` interface.');
        }

        return $vendor->orderStatus($order);
    }

    /**
     * @throws ErrorException
     */
    public function cancelOrder(BaseModel $order): mixed
    {
        $vendor = $this->getVendorInstance($vendor_slug);

        if (!$vendor instanceof OrderQuotation) {
            throw new ErrorException('Service Vendor Class is not instance of `Fintech\Remit\Contracts\OrderQuotation` interface.');
        }

        return $vendor->requestQuote($order);
    }

    public function amendmentOrder(BaseModel $order): mixed
    {
        $vendor = $this->getVendorInstance($vendor_slug);

        if (!$vendor instanceof OrderQuotation) {
            throw new ErrorException('Service Vendor Class is not instance of `Fintech\Remit\Contracts\OrderQuotation` interface.');
        }

        return $vendor->requestQuote($order);
    }
}

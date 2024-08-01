<?php

namespace Fintech\Remit\Services;

use ErrorException;
use Exception;
use Fintech\Business\Facades\Business;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Remit\Contracts\OrderInquiry;
use Fintech\Remit\Contracts\OrderQuotation;
use Fintech\Remit\Contracts\ProceedOrder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;

class AssignVendorService
{
    private function getVendorDriverInstance(string $slug): mixed
    {
        $availableVendors = config('fintech.remit.providers', []);

        if (! isset($availableVendors[$slug])) {
            throw new ErrorException('Service Vendor is not available on the configuration.');
        }

        $vendor = $availableVendors[$slug];

        $driverClass = $vendor['driver'];

        return App::make($driverClass);
    }

    
    /**
     * @throws \Fintech\Transaction\Exceptions\AlreadyAssignedException
     */
    public function availableVendors(BaseModel $order): Collection
    {
        if($order->assignedUser != null && $order->assignedUser->id != request()->user()->id) {
            throw new \Fintech\Transaction\Exceptions\AlreadyAssignedException("This order is already assigned by another");
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
        $vendor = $this->getVendorDriverInstance($vendor_slug);

        if (! $vendor instanceof OrderQuotation) {
            throw new ErrorException('Service Vendor Class is not instance of `Fintech\Remit\Contracts\OrderQuotation` interface.');
        }

        return $vendor->requestQuote($order);
    }

    /**
     * @throws ErrorException
     */
    public function processOrder(BaseModel $order, string $vendor_slug): mixed
    {
        $vendor = $this->getVendorDriverInstance($vendor_slug);

        if (! $vendor instanceof ProceedOrder) {
            throw new ErrorException('Service Vendor Class is not instance of `Fintech\Remit\Contracts\ProceedOrder` interface.');
        }

        return $vendor->processOrder($order);
    }

    /**
     * @throws ErrorException
     */
    public function orderStatus(BaseModel $order, string $vendor_slug): mixed
    {
        $vendor = $this->getVendorDriverInstance($vendor_slug);

        if (! $vendor instanceof OrderInquiry) {
            throw new ErrorException('Service Vendor Class is not instance of `Fintech\Remit\Contracts\OrderInquiry` interface.');
        }

        return $vendor->orderStatus($order);
    }

    /**
     * @throws ErrorException
     */
    public function cancelOrder(BaseModel $order, string $vendor_slug): mixed
    {
        $vendor = $this->getVendorDriverInstance($vendor_slug);

        if (! $vendor instanceof OrderQuotation) {
            throw new ErrorException('Service Vendor Class is not instance of `Fintech\Remit\Contracts\OrderQuotation` interface.');
        }

        return $vendor->requestQuote($order);
    }
}

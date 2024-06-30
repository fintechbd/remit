<?php

namespace Fintech\Remit\Services;

use ErrorException;
use Fintech\Business\Facades\Business;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Remit\Contracts\OrderQuotation;
use Fintech\Remit\Contracts\ProceedOrder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;

class AssignVendorService
{
    public function availableVendors(BaseModel $order): Collection
    {
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
        $availableVendors = config('fintech.remit.providers', []);

        if (! isset($availableVendors[$vendor_slug])) {
            throw new ErrorException('Service Vendor is not available on the configuration.');
        }

        $vendor = $availableVendors[$vendor_slug];

        $driverClass = $vendor['driver'];

        $instance = App::make($driverClass);

        if (! $instance instanceof OrderQuotation) {
            throw new ErrorException('Service Vendor Class is not instance of `Fintech\Remit\Contracts\OrderQuotation` interface.');
        }

        return $instance->requestQuote($order);
    }

    public function processOrder(BaseModel $order, string $vendor_slug): mixed
    {
        $availableVendors = config('fintech.remit.providers', []);

        if (! isset($availableVendors[$vendor_slug])) {
            throw new ErrorException('Service Vendor is not available on the configuration.');
        }

        $vendor = $availableVendors[$vendor_slug];

        $driverClass = $vendor['driver'];

        /**
         * @var $instance ProceedOrder
         */
        $instance = App::make($driverClass);

        if (! $instance instanceof ProceedOrder) {
            throw new ErrorException('Service Vendor Class is not instance of `Fintech\Remit\Contracts\ProceedOrder` interface.');
        }

        return $instance->processOrder($order);
    }

    public function orderStatus(BaseModel $order, string $vendor_slug): mixed
    {
        $availableVendors = config('fintech.remit.providers', []);

        if (! isset($availableVendors[$vendor_slug])) {
            throw new ErrorException('Service Vendor is not available on the configuration.');
        }

        $vendor = $availableVendors[$vendor_slug];

        $driverClass = $vendor['driver'];

        $instance = App::make($driverClass);

        if (! $instance instanceof OrderQuotation) {
            throw new ErrorException('Service Vendor Class is not instance of `Fintech\Remit\Contracts\OrderQuotation` interface.');
        }

        return $instance->requestQuote($order);
    }

    public function cancelOrder(BaseModel $order, string $vendor_slug): mixed
    {
        $availableVendors = config('fintech.remit.providers', []);

        if (! isset($availableVendors[$vendor_slug])) {
            throw new ErrorException('Service Vendor is not available on the configuration.');
        }

        $vendor = $availableVendors[$vendor_slug];

        $driverClass = $vendor['driver'];

        $instance = App::make($driverClass);

        if (! $instance instanceof OrderQuotation) {
            throw new ErrorException('Service Vendor Class is not instance of `Fintech\Remit\Contracts\OrderQuotation` interface.');
        }

        return $instance->requestQuote($order);
    }
}

<?php

namespace Fintech\Remit\Contracts;

use ErrorException;
use Fintech\Core\Abstracts\BaseModel;

interface WalletTransfer
{
    /**
     * Method to make a request to the remittance service provider
     * for a quotation of the order. that include charge, fee,
     * commission and other information related to order.
     *
     * @throws ErrorException
     */
    public function validateWallet(BaseModel $order): mixed;

    /**
     * Method to make a request to the remittance service provider
     * for an execution of the order.
     *
     * @throws ErrorException
     */
    public function executeOrder(BaseModel $order): mixed;

    /**
     * Method to make a request to the remittance service provider
     * for the progress status of the order.
     *
     * @throws ErrorException
     */
    public function orderStatus(BaseModel $order): mixed;

    /**
     * Method to make a request to the remittance service provider
     * for the track real-time progress of the order.
     *
     * @throws ErrorException
     */
    public function trackOrder(BaseModel $order): mixed;

    /**
     * Method to make a request to the remittance service provider
     * for the cancellation of the order.
     *
     * @throws ErrorException
     */
    public function cancelOrder(BaseModel $order): mixed;

    /**
     * Method to make a request to the remittance service provider
     * for the amendment of the order.
     *
     * @throws ErrorException
     */
    public function amendmentOrder(BaseModel $order): mixed;
}
